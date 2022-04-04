#!/bin/bash

EC2_ASG=stage-asg
OLD_DB_NAME="bmsdb"

while [ $(aws autoscaling describe-auto-scaling-groups --auto-scaling-group-name ${EC2_ASG} --query 'length(AutoScalingGroups[*].Instances[?LifecycleState==`InService`][])') -gt 1 ] ; do
  aws autoscaling set-desired-capacity --auto-scaling-group-name ${EC2_ASG} --desired-capacity 1
  echo "waiting for scale down, sleep for 20s"
  sleep 20;
done
INSTANCE_ID=$(aws autoscaling describe-auto-scaling-groups --auto-scaling-group-name ${EC2_ASG} --output text --query 'AutoScalingGroups[*].Instances[?LifecycleState==`InService`].InstanceId')
ec2_host=$(aws ec2 describe-instances --instance-ids ${INSTANCE_ID} --output text --query 'Reservations[*].Instances[*].PublicIpAddress')

# add host to known_hosts
if [[ -z `ssh-keygen -F $ec2_host` ]]; then
  ssh-keyscan -H $ec2_host >> ~/.ssh/known_hosts
fi

export command="sed -i -e \"s/${OLD_DB_NAME}/${DATABASE_NAME}/g\" /opt/humansis/parameters.yml"
ssh ec2-user@${ec2_host} $command
ssh ec2-user@${ec2_host} "cd /opt/humansis && /opt/humansis/clear-cache.sh aggressive"
ssh ec2-user@${ec2_host} "cd /opt/humansis && sudo docker-compose exec -T php bash -c 'php bin/console doctrine:migrations:migrate -n'"
