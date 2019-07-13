# 中央储存服务

## 背景

- 图片存储作为基础服务，独立于各个应用外，通过远程调用发送图片到存储服务中。存储服务集中管理文件的创建，复制，删除以及CDN服务 

## 客户端程序

### 注入phalcon


```php
$di->set('cloudStorage', function () use ($config) {
    return new CloudStorage([
        'version' => 'GridFs',
        'api' => 'http://disk.xy.cn/',
        'imagePrefix' => 'http://cdn.xy.cn/',
        'tag' => 'insurance',
        'appId' => 'test',
        'appSecret' => 'test123456',
    ]);
});
```

- 参数配置说明


| 参数       |  类型   |默认值 | 说明       |
|------------|--------|--------|--------------|
|version     | string | default| 版本，GridFs和default分别使用两种存储方式，不填写默认为default  |
|api         | string |        | 服务地址http  |
|appId       | string |        | 访问凭证ID   |
|appSecret   | string |        | 访问凭密码   |
|imagePrefix | string |        | CDN地址                         | 
|pathType    | string | WEEK   | 生成日期文件的类型WEEK DAY MONTH|
|tag         | string |        | 定义私有根目录，需要服务端开通可以使用的TAG    |
|allowed     | array  |        | 允许上传的文件格式，数组默认图片的mime信息，需要小于等于服务端的设置 |
|maxSize     | int    |        | 允许上传的文件大小,单位mb，需要小于服务端的设置  |
|gzip        | boolean| true   | 传输开启gzip压缩  |

DEV测试api参数为

```
api: http://disk.xy.cn/
appId: test
appSecret: test123456
```

### 调用上传

#### 单文件上传

```
$result = $cloudStorage->setFile($_FILES[0]['tmp_name'])->upload();
if ($result === false) {
    return $app->apiResponse->error($cloudStorage->getError());
}
$images = $cloudStorage->getResult();
```

返回的数据


```json
[
    {
        "status": "200",
        "oid": "5d29f7b04e110f00085fbab2",
        "path": "insurance/201928/2332727541.jpeg",
        "url": "http://cdn.xy.cn/insurance/201928/2332727541.jpeg"
    }
]
```

#### 指定tag上传

```
$result = $cloudStorage->setFile($_FILES[0]['tmp_name'])->setTag('test')->upload();
if ($result === false) {
    return $app->apiResponse->error($cloudStorage->getError());
}
$images = $cloudStorage->getResult();
```

#### 批量上传
```
$cloudStorage = $app->cloudStorage;
$files = array_column($_FILES, 'tmp_name');
$cloudStorage->setFiles($files);
$cloudStorage->setTag('insurance');
if ($cloudStorage->upload() === false) {
    echo $cloudStorage->getError();
}
$images = $cloudStorage->getResult();
```

返回的数据


```json
[
    {
        "status": "200",
        "oid": "5d29f7b04e110f00085fbab2",
        "path": "insurance/201928/2332727541.jpeg",
        "url": "http://cdn.xy.cn/insurance/201928/2332727541.jpeg"
    },
    {
        "status": "200",
        "oid": "5d29f7b04e110f00085fbab2",
        "path": "insurance/201928/106785628.jpeg",
        "url": "http://cdn.xy.cn/insurance/201928/106785628.jpeg"
    }
]
```

本地约束限制设置（服务端针对appid也有限制，权限应该在服务端的限制之下）

```
$result = $app->cloudStorage->setAllowed([
     'image/jpeg',
     'image/jpg',
     'image/png',
     'application/zip',
     'application/x-rar',
     'application/x-zip-compressed',
 ])
 ->setMaxSize(5)
 ->setFiles(array_column($_FILES, 'tmp_name'))
 ->setTag('insurance')->upload();
if ($result === false) {
   echo $app->cloudStorage->getError()
}
```

### 删除文件

```php
$cloudStorage = $app->cloudStorage;
if ($cloudStorage->remove(['insurance/201928/106785628.jpeg','insurance/201928/2332727541.jpeg']) === false) {
    echo  $cloudStorage->getError();
}
    
```


### 调用地址

#### 获取CDN地址

> 上传接口返回的path一般本地存取，通过拼装函数获得最终CDN地址


```php
echo $app->cloudStorage->getStaticUrl('insurance/201928/2332727541.jpeg');
```

输出数据

```
http://cdn.xy.cn/insurance/201928/2332727541.jpeg
```

#### 图片在线裁剪（需要CDN支持）

```php
echo $app->cloudStorage->getStaticUrl('insurance/201928/2332727541.jpeg',['width'=>100,'height'=>100,clipType='1']);
```


