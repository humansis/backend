# Sync

## Properties
Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**id** | **int** |  | [optional] 
**state** | [**Humansis\WebApi\Model\SyncStateEnum**](SyncStateEnum.md) |  | [optional] 
**source** | [**Humansis\WebApi\Model\SourceEnum**](SourceEnum.md) |  | [optional] 
**createdAt** | **string** |  | [optional] 
**createdBy** | **int** | UserId, see GET /users/{id} | [optional] 
**vendorId** | **int** | VendorId, see GET /vendors/{id} | [optional] 
**validatedAt** | **string** |  | [optional] 
**rawData** | **string** | whole sended data in JSON format | [optional] 
**violations** | **array** | validation errors, keys are same as keys in rawData | [optional] 

[[Back to Model list]](../README.md#documentation-for-models) [[Back to API list]](../README.md#documentation-for-api-endpoints) [[Back to README]](../README.md)


