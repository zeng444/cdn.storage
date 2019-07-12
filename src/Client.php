<?php

namespace Janfish\Storage;

use Exception;

/**
 * Author:Robert Tsang
 *
 * Class Client
 * @package Storage
 */
class Client
{


    /**
     * Author:Robert
     *
     * @var mixed
     */
    protected $imagePrefix;

    /**
     * 接口地址http
     * Author:Robert
     *
     * @var mixed|string
     */
    protected $api = '';

    /**
     * Author:Robert
     *
     * @var mixed|string
     */
    protected $appId = '';

    /**
     * Author:Robert
     *
     * @var bool|mixed
     */
    protected $gzip = true;

    /**
     * Author:Robert
     *
     * @var string
     */
    protected $appSecret = '';

    /**
     * Author:Robert
     *
     * @var mixed|string
     */
    protected $pathType = 'WEEK';


    /**
     * Author:Robert
     *
     * @var array
     */
    protected $files = [];


    /**
     * Author:Robert
     *
     * @var array
     */
    protected $response = [];

    /**
     * 项目标签
     * Author:Robert
     *
     * @var mixed|string
     */
    protected $tag = 'default';

    /**
     * Author:Robert Tsang
     *
     * @var string
     */
    protected $error = '';

    /**
     * Author:Robert
     *
     * @var array
     */
    public static $pathTypeMap = ['DAY', 'WEEK', 'MONTH'];

    /**
     * Author:Robert
     *
     * @var array
     */
    protected $allowed = [
        'image/jpeg',
        'image/pjpeg',
        'image/jpg',
        'image/png',
        'image/x-png',
    ];

    /**
     * Author:Robert
     *
     * @var int
     */
    protected $maxSize = 5;

    const PATH_INFO = 'attachments';


    /**
     * Client constructor.
     * @param array $options
     * @throws Exception
     */
    public function __construct(array $options = [])
    {
        if (isset($options['api'])) {
            $this->api = $options['api'];
        }
        if (isset($options['imagePrefix'])) {
            $this->imagePrefix = $options['imagePrefix'];
        }
        if (isset($options['pathType'])) {
            $this->pathType = $options['pathType'];
        }
        if (isset($options['tag'])) {
            $this->tag = $options['tag'];
        }
        if (isset($options['appId'])) {
            $this->appId = $options['appId'];
        }
        if (isset($options['gzip'])) {
            $this->gzip = $options['gzip'];
        }
        if (isset($options['appSecret'])) {
            $this->appSecret = $options['appSecret'];
        }
        if (isset($options['allowed'])) {
            $this->allowed = $options['allowed'];
        }
        if (isset($options['maxSize'])) {
            $this->maxSize = $options['maxSize'];
        }
        if (!$this->api) {
            throw new Exception('对不起，请先配置文件存储服务');
        }
        if (!preg_match('/^\w+$/', $this->tag)) {
            throw new Exception('对不起tag格式不合法');
        }
    }

    /**
     * Author:Robert
     *
     * @param array $allowed
     * @return $this
     */
    public function setAllowed(array $allowed)
    {
        $this->allowed = $allowed;
        return $this;
    }

    /**
     * Author:Robert
     *
     * @param int $size
     * @return $this
     */
    public function setMaxSize(int $size)
    {
        $this->maxSize = $size;
        return $this;
    }

    /**
     * Author:Robert
     *
     * @param string $tag
     * @return $this
     */
    public function setTag(string $tag)
    {
        $this->tag = $tag;
        return $this;
    }

    /**
     * Author:Robert
     *
     * @param string $file
     * @return $this
     * @throws Exception
     */
    public function setFile(string $file)
    {
        if (!$file || !file_exists($file)) {
            throw new Exception('文件路径不存在，请检查'.$file, 500);
        }
        $this->files[] = [
            'file' => $file,
            'size' => filesize($file),
            'mime' => mime_content_type($file),
        ];
        return $this;
    }

    /**
     * Author:Robert
     *
     * @param $files
     * @return $this
     * @throws Exception
     */
    public function setFiles($files)
    {
        $files = is_array($files) ? $files : [$files];
        foreach ($files as $file) {
            $this->setFile($file);
        }
        return $this;
    }

    /**
     * Author:Robert Tsang
     *
     * @return bool
     * @throws Exception
     */
    public function checkFiles(): bool
    {
        foreach ($this->files as &$file) {
            if (!$file || !file_exists($file['file'])) {
                throw new Exception('文件路径不存在，请检查'.$file['file'], 500);
            }
            if (!in_array($file['mime'], $this->allowed)) {
                $this->error = '不允许上传的文件格式类型:'.$file['mime'];
                return false;
            }
            if ($file['size'] > $this->maxSize * 1048576) {
                $this->error = '文件大小超过允许的最大值:'.$this->maxSize.'m';
                return false;
            }
        }
        return true;
    }

    /**
     * Author:Robert
     *
     * @return bool
     * @throws Exception
     */
    public function upload(): bool
    {
        if ($this->checkFiles() === false) {
            $this->files = [];
            return false;
        }
        $res = json_decode($this->http($this->getApiUrl(), 'POST', [
            'time' => time(),
            'tag' => $this->tag,
            'pathType' => $this->pathType,
        ], $this->files), true);
        $this->files = [];
        if (!$res) {
            throw new Exception('数据的格式返回返回', 500);
        }
        if (!isset($res['status']) || $res['status'] !== 'success') {
            $this->error = $res['message'] ?? '文件上传错误';
            return false;
        }
        $this->response = $res['data']['attachments'] ?? [];
        return true;
    }

    /**
     * Author:Robert Tsang
     *
     * @param $files
     * @return bool
     * @throws Exception
     */
    public function remove($files): bool
    {
        $files = is_array($files) ? $files : [$files];
        $res = json_decode($this->http($this->getApiUrl(), 'DELETE', ['files' => $files]), true);
        if (!$res) {
            throw new Exception('数据的格式返回返回', 500);
        }
        if (!isset($res['status']) || $res['status'] !== 'success') {
            $this->error = $res['message'] ?? '删除文件失败';
            return false;
        }
        $this->response = $res['data']['attachments'] ?? [];
        return true;
    }


    /**
     * Author:Robert
     *
     * @return string
     */
    public function getError(): string
    {
        return $this->error;
    }


    /**
     * Author:Robert
     *
     * @param string $file
     * @param array $opt
     * @return string
     */
    public function getStaticUrl(string $file = '', $opt = []): string
    {
        return $this->imagePrefix.'/'.$file;
    }

    /**
     * Author:Robert
     *
     * @param int $index
     * @return int
     */
    public function getSize(int $index = 0): int
    {
        return $this->files[$index]['size'];
    }


    /**
     * Author:Robert
     *
     * @param int $index
     * @return string
     */
    public function getMime(int $index = 0): string
    {
        return $this->files[$index]['mime'];
    }

    /**
     * Author:Robert
     *
     * @return array
     */
    public function getResult()
    {
        return $this->response;
    }

    /**
     * Author:Robert
     *
     * @return string
     */
    public function makeBasicToken(): string
    {
        return "Authorization: Basic ".base64_encode(sprintf('%s:%s', $this->appId, $this->appSecret));
    }


    /**
     * 地址
     * Author:Robert
     *
     * @return string
     */
    protected function getApiUrl(): string
    {
        return sprintf('%s%s', $this->api, self::PATH_INFO);
    }

    /**
     * Author:Robert Tsang1
     *
     * @param string $api
     * @param string $method
     * @param array $fields
     * @param array $files
     * @return mixed
     * @throws Exception
     */
    public function http(string $api, string $method, array $fields = [], array $files = []): string
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        curl_setopt($curl, CURLOPT_USERAGENT, "X.Y R&D Apollo Program");
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            $this->makeBasicToken(),
        ]);
        if (in_array($method, ['POST', 'PUT'])) {
            curl_setopt($curl, CURLOPT_POST, 1);
        }
        if (in_array($method, ['POST']) && $files) {
            curl_setopt($curl, CURLOPT_SAFE_UPLOAD, 1);
            foreach ($files as $index => $file) {
                $fields["file$index"] = curl_file_create($file['file'], ($file['mime']));
            }
        }
        if ($fields) {
            if (in_array($method, ['PUT', 'POST'])) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
            } else {
                $api .= '?'.http_build_query($fields);
            }
        }
        curl_setopt($curl, CURLOPT_URL, $api);
        if ($method) {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        }
        if ($this->gzip === true) {
            curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($curl);
        if (curl_errno($curl)) {
            throw new Exception(curl_error($curl), 0);
        }
        $httpStatusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if (200 !== $httpStatusCode) {
            throw new Exception($res, $httpStatusCode);
        }
        return $res;
    }


}
