Test organization
=================

Types of tests and its responsibilities:
 - Smoke tests
    - Should only check if API doesn't fall with HTTP error 500
     - check both options: correct data AND user wrong data
    - All controller actions **MUST** have it
    - Path: tests/AcmeBundle/Controller/SpecificControllerTest.php
 - Service tests
    - Every service should have intensive test of behavior
    - Test all possible valid inputs and possible wrong outputs
    - Path: tests/AcmeBundle/Service/SpecificService/SpecificActionTest.php
 - Authorization tests
    - Test API and Service methods used in controllers
    - Every service test should have complete (ROLE, access) matrix for testing methods   
    - Path: tests/AcmeBundle/Service/SpecificService/AuthorizationTest.php
 - Business tests
    - Optional
    - Mandatory for entities with workflow or complex creation or behavior
    - Path should correspond with real class namespace
    - Path choose by test complexity:
        - tests/AcmeBundle/ClassType/BusinessClassName/SpecificMethodTest.php
        - tests/AcmeBundle/ClassType/BusinessClassNameTest.php
 - Integration tests
    - Testing of Frontend and Backend cooperation are click-simulated tests in **Humansis/front** repository