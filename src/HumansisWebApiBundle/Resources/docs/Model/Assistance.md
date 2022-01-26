# Assistance

## Properties
Name | Type | Description | Notes
------------ | ------------- | ------------- | -------------
**id** | **int** | Primary identifier | [optional] [readonly] 
**iso3** | **string** | Unique ISO code of country | [optional] 
**name** | **string** |  | [optional] [readonly] 
**dateDistribution** | **string** |  | [optional] 
**dateExpiration** | **string** |  | [optional] 
**description** | **string** |  | [optional] 
**projectId** | **int** | Primary ID of project | [optional] 
**target** | **string** | one of codes from code list /assistances/targets | [optional] 
**type** | **string** | one of codes from code list /assistances/types | [optional] 
**sector** | **string** | one of codes from code list /sectors | [optional] 
**subsector** | **string** | one of codes from code list /sectors/subsectors | [optional] 
**locationId** | **int** |  | [optional] 
**adm1Id** | **int** |  | [optional] [readonly] 
**adm2Id** | **int** |  | [optional] [readonly] 
**adm3Id** | **int** |  | [optional] [readonly] 
**adm4Id** | **int** |  | [optional] [readonly] 
**commodityIds** | **int** |  | [optional] [readonly] 
**commodities** | [**Humansis\WebApi\Model\Commodity**](Commodity.md) |  | [optional] 
**selectionCriteriaIds** | **int** |  | [optional] [readonly] 
**selectionCriteria** | [**Humansis\WebApi\Model\SelectionCriterion**](SelectionCriterion.md) |  | [optional] 
**threshold** | **int** |  | [optional] 
**completed** | **bool** |  | [optional] 
**validated** | **bool** |  | [optional] 
**deletable** | **bool** |  | [optional] [readonly] 
**distributionStarted** | **bool** |  | [optional] [readonly] 
**remoteDistributionAllowed** | **bool** |  | [optional] 
**allowedProductCategoryTypes** | [**Humansis\WebApi\Model\ProductCategoryType**](ProductCategoryType.md) |  | [optional] 
**cashbackLimit** | **string** |  | [optional] 

[[Back to Model list]](../README.md#documentation-for-models) [[Back to API list]](../README.md#documentation-for-api-endpoints) [[Back to README]](../README.md)


