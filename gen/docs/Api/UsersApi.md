# OpenAPI\Client\UsersApi

All URIs are relative to http://localhost/api, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**apiUsersGet()**](UsersApi.md#apiUsersGet) | **GET** /api/users | Get all users |
| [**apiUsersIdGet()**](UsersApi.md#apiUsersIdGet) | **GET** /api/users/{id} | Get a user by ID |


## `apiUsersGet()`

```php
apiUsersGet(): \OpenAPI\Client\Model\User[]
```

Get all users

Retrieve all users in the system

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');



$apiInstance = new OpenAPI\Client\Api\UsersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);

try {
    $result = $apiInstance->apiUsersGet();
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling UsersApi->apiUsersGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

This endpoint does not need any parameter.

### Return type

[**\OpenAPI\Client\Model\User[]**](../Model/User.md)

### Authorization

No authorization required

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `apiUsersIdGet()`

```php
apiUsersIdGet($id): \OpenAPI\Client\Model\User
```

Get a user by ID

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');



$apiInstance = new OpenAPI\Client\Api\UsersApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client()
);
$id = 56; // int | The ID of the user

try {
    $result = $apiInstance->apiUsersIdGet($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling UsersApi->apiUsersIdGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| The ID of the user | |

### Return type

[**\OpenAPI\Client\Model\User**](../Model/User.md)

### Authorization

No authorization required

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
