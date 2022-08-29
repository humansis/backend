#!/bin/bash
CONTAINER_NAME=$1
INSTANCE_ID=$(curl -s http://169.254.169.254/latest/meta-data/instance-id)
REGION=$(curl -s http://169.254.169.254/latest/meta-data/placement/region)

function set_instance_health () {
	aws autoscaling set-instance-health --instance-id $INSTANCE_ID  --region $REGION --health-status Unhealthy
}

IS_RUNNING=$(docker inspect -f "{{.State.Running}}" $CONTAINER_NAME) >> /dev/null 2>&1
if [[ $? -eq 1 ]]
then
	echo "Docker daemon is down"
	set_instance_health
	exit 1
fi

if [[ "$IS_RUNNING" == "false" ]]
then
	echo "Container is not running"
	set_instance_health
	exit 1
fi

RESTART_COUNT=$(docker inspect -f "{{.RestartCount}}" $CONTAINER_NAME) >> /dev/null 2>&1
if [[ $? -eq 1 ]]
then
	echo "Docker daemon is down"
	set_instance_health
	exit 1
fi

if [[ $RESTART_COUNT -eq 100 ]]
then
	echo "More than 100 restarts of the container"
	set_instance_health
	exit 1
fi

#EVENTS=$(docker events --filter event=start --filter container=$CONTAINER_NAME --since=1h --until=1s) >> /dev/null 2>&1
#if [[ $? -eq 1 ]]
#then
#	echo "Docker daemon is down"
#	set_instance_health
#	exit 1
#fi

#num=$(echo -n "$EVENTS" | grep -c '^')
#if [[ $num -gt 10 ]]
#then
#	echo "More than 10 starts in 1 hours"
#	set_instance_health
#	exit 1
#fi
