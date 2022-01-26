# Household

## Properties
Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**id** | **int** | Primary identifier | [optional] [readonly] 
**iso3** | **string** | Unique ISO code of country | [optional] 
**livelihood** | **string** | one of codes from code list /households/livelihoods | [optional] 
**assets** | **string** | one of codes from code list /households/assets | [optional] 
**shelterStatus** | **string** | one of codes from code list /households/shelter-statuses | [optional] 
**projectIds** | **int** |  | [optional] 
**notes** | **string** |  | [optional] 
**longitude** | **string** |  | [optional] 
**latitude** | **string** |  | [optional] 
**householdHeadId** | **int** | ID of HHH beneficiary | [optional] [readonly] 
**beneficiaryIds** | **int** |  | [optional] [readonly] 
**beneficiaries** | [**Humansis\WebApi\Model\Beneficiary**](Beneficiary.md) |  | [optional] 
**incomeLevel** | **int** |  | [optional] 
**foodConsumptionScore** | **int** |  | [optional] 
**copingStrategiesIndex** | **int** |  | [optional] 
**debtLevel** | **int** |  | [optional] 
**supportDateReceived** | **string** |  | [optional] 
**supportReceivedTypes** | **int** |  | [optional] 
**supportOrganizationName** | **string** |  | [optional] 
**incomeSpentOnFood** | **int** |  | [optional] 
**houseIncome** | **int** |  | [optional] 
**countrySpecificAnswerIds** | **int** |  | [optional] [readonly] 
**countrySpecificAnswers** | [**Humansis\WebApi\Model\CountrySpecificAnswer**](CountrySpecificAnswer.md) |  | [optional] 
**campAddress** | [**AnyType**](AnyType.md) |  | [optional] 
**campAddressId** | **int** | ID of CampAddress | [optional] [readonly] 
**residenceAddress** | [**AnyType**](AnyType.md) |  | [optional] 
**residenceAddressId** | **int** | ID of ResidencyAddress | [optional] [readonly] 
**temporarySettlementAddress** | [**AnyType**](AnyType.md) |  | [optional] 
**temporarySettlementAddressId** | **int** | ID of TemporarySettlementAddress | [optional] [readonly] 
**proxyLocalFamilyName** | **string** | Mandatory if any of proxy field is filled | [optional] 
**proxyLocalGivenName** | **string** | Mandatory if any of proxy field is filled | [optional] 
**proxyLocalParentsName** | **string** |  | [optional] 
**proxyEnFamilyName** | **string** |  | [optional] 
**proxyEnGivenName** | **string** |  | [optional] 
**proxyEnParentsName** | **string** |  | [optional] 
**proxyNationalIdCard** | [**NationalID**](NationalID.md) |  | [optional] 
**proxyPhone** | [**Phone**](Phone.md) |  | [optional] 
**proxyNationalIdCardId** | **int** |  | [optional] [readonly] 
**proxyPhoneId** | **int** |  | [optional] [readonly] 

[[Back to Model list]](../README.md#documentation-for-models) [[Back to API list]](../README.md#documentation-for-api-endpoints) [[Back to README]](../README.md)


