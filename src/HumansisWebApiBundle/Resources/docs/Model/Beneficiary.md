# Beneficiary

## Properties
Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**dateOfBirth** | **string** |  | 
**localFamilyName** | **string** |  | 
**localGivenName** | **string** |  | 
**isHead** | **bool** | true &#x3D; Beneficiary is HH head, otherwise is HH member | 
**id** | **int** | Primary identifier. Needs to be set when editing household members. | [optional] 
**localParentsName** | **string** |  | [optional] 
**enFamilyName** | **string** |  | [optional] 
**enGivenName** | **string** |  | [optional] 
**enParentsName** | **string** |  | [optional] 
**gender** | **string** |  | [optional] 
**nationalIdCards** | [**Humansis\WebApi\Model\NationalID**](NationalID.md) |  | [optional] 
**nationalIds** | **int** |  | [optional] [readonly] 
**phones** | [**Humansis\WebApi\Model\Phone**](Phone.md) |  | [optional] 
**phoneIds** | **int** |  | [optional] [readonly] 
**referralType** | **string** | see /beneficiary/referral-types | [optional] 
**referralComment** | **string** |  | [optional] 
**residencyStatus** | **string** | see /beneficiaries/residency-statuses | [optional] 
**vulnerabilityCriteria** | **string** |  | [optional] 
**householdId** | **int** |  | [optional] 

[[Back to Model list]](../README.md#documentation-for-models) [[Back to API list]](../README.md#documentation-for-api-endpoints) [[Back to README]](../README.md)


