<?php

namespace Vendor\HttpClient;

use Vendor\HttpClient\HttpClientException;

class HttpClient
{
    protected $ch;
    private $method ='GET';
    protected $defaultHeaders = [];
    protected $defaultOptions = [];
    private $encode;
    private $urlBase;
    private $urlResource;

    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }
    
    public function getMethod()
    {
        return strtoupper($this->method);
    }

    public function send()
    {
        $this->prepareRequest();

        $result = curl_exec($this->ch);
        
        if ($result === false) {
            $errno = curl_errno($this->ch);
            $errmsg = curl_error($this->ch);
            $msg = "Falha ao requistar [$errno]: $errmsg";
            curl_close($this->ch);
            throw new HttpClientException(json_encode([$this->getUrl(), $msg, $errno]));
        }

        $this->setResponse($result);

        curl_close($this->ch);
        return $this;
    }

    public function prepareRequest()
    {
        $this->ch = curl_init();
        $configCurl[CURLOPT_RETURNTRANSFER] = true;
        $configCurl[CURLOPT_HEADER] = true;
        $configCurl[CURLOPT_SSL_VERIFYPEER] = false;
        $configCurl[CURLOPT_URL] = $this->getUrl();

        $options = $this->getOptions();
        if (!empty($options)) {
            curl_setopt_array($this->ch, $options);
        }

        $configCurl[CURLOPT_CUSTOMREQUEST] = $this->getMethod();

        if (!empty($this->headers)) {
            $configCurl[CURLOPT_HTTPHEADER] = $this->formatHeaders();
        }
        
        if (!empty($this->data) && $this->getMethod() == 'POST') {
            $configCurl[CURLOPT_POST] = true;
            $configCurl[CURLOPT_POSTFIELDS] = $this->encodeData();
            $this->postData = $this->getData();
            
        }

        if (!empty($this->data) && $this->getMethod() == 'PUT') {
            $putString = 'str='.urlencode($this->encodeData());
            $putFile = tmpfile();
            fwrite($putFile, $putString);
            fseek($putFile, 0);
           
            $configCurl[CURLOPT_PUT] = true;
            $configCurl[CURLOPT_INFILE]=  $putFile;
            $configCurl[CURLOPT_INFILESIZE]= strlen($putString);
        }
        
        curl_setopt_array($this->ch, $configCurl);
    }
    
    public function setUrlBase($url)
    {
        $this->urlBase = $url;
        return $this;
    }

    public function getUrlBase()
    {
        return $this->urlBase;
    }
    public function setUrlResource($url)
    {
        $this->urlResource = $url;
        return $this;
    }

    public function getUrlResource()
    {
        return $this->urlResource;
    }
    
    public function getUrl()
    {
        return $this->urlBase.$this->urlResource;
    }

    public function getHeaders()
    {
        return $this->headers;
    }
    
    public function setHeader($key, $value = null)
    {
        if ($value === null) {
            list($key, $value) = explode(':', $value, 2);
        }
        $key = trim($key);
        $this->headers[$key] = trim($value);

        return $this;
    }

    public function setOption($key, $value)
    {
        $this->options[$key] = $value;
        return $this;
    }

    public function getOptions()
    {
        return $this->options;
    }

    private function setUtf8(array $arrData)
    {
        foreach ($arrData as $key => $v) {
            if (\is_array($v)) {
                $arrData[$key] = $this->setUtf8($v);
            } elseif ($v) {
                $arrData[$key] = utf8_encode($v);
            }
        }
        return $arrData;
    }

    public function encodeData()
    {
        $this->data = $this->setUtf8($this->data);
        if ($this->encoding == 'json') {
            return json_encode($this->data);
        }
        return (!is_null($this->data) ? http_build_query($this->data) : '');
    }

    public function setEncoding($encoding)
    {
        if ($encoding =='json') {
            $this->setHeader('Content-Type', 'application/json');
        }
        $this->encoding = $encoding;
        return $this;
    }

    public function formatHeaders()
    {
        $headers = [];
        if (empty($this->headers)) {
            return;
        }

        foreach ($this->headers as $key => $val) {
            $headers[] = $key . ': ' . $val;
        }
        return $headers;
    }
    
    public function setData($data)
    {
        if ($this->getMethod() == 'POST') {
            $this->postData = $data;    
        }
        
        $this->data = $data;
        return $this;
    }
    
    public function getData()
    {
        return $this->data;
    }

    public function getPostData()
    {
        return $this->postData;
    }

    private function setResponse($response)
    {
        $headerSize = curl_getinfo($this->ch, CURLINFO_HEADER_SIZE);

        $this->response = new HttpResponse(
            substr($response, 0, $headerSize),
            substr($response, $headerSize),
            curl_getinfo($this->ch)
        );
    }

    public function getResponse()
    {
        return $this->response;
    }
    
    

}
