<?php
/*
 * Database.php
 * Octorate srl. All rights reserved. 2019 
 */

namespace com\octorate\stream\utils;

class cURL
{

    public $headers = Array();
    
    public $respHead;

    public $user_agent;

    public $compression;

    public $cookie_file;

    public $proxy;

    public $proxyAuther;

    public function cURL($cookies = TRUE, $cookie = 'cookies.txt', $compression = 'gzip,deflate', $proxy = '')
    {
        $this->headers[] = "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8";
        $this->headers[] = "Connection: Keep-Alive";
        $this->headers[] = "Accept-Language: en-US,en;q=0.5";
        $this->headers[] = "Content-type: application/x-www-form-urlencoded";
        //$this->user_agent = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.1.7) Gecko/20091221 Firefox/3.5.7";
        $this->user_agent = "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:42.0) Gecko/20100101 Firefox/42.0";
        //$this->user_agent = "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; .NET CLR 2.0.50727; .NET CLR 3.0.04506.648; .NET CLR 3.5.21022; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729)";
        $this->compression = $compression;
        $this->proxy = $proxy;
        $this->cookies = $cookies;
        if ($this->cookies == TRUE) $this->cookie($cookie);
    }

    public function cookie($cookie_file)
    {
        if (file_exists($cookie_file)) {
            $this->cookie_file = $cookie_file;
        } else {
            @fopen($cookie_file, 'w') or $this->error("The cookie file could not be opened. Make sure this directory has the correct permissions");
            $this->cookie_file = $cookie_file;
            fclose($cookie_file);
        }
    }

    public function get($url, $refer = '')
    {
        $process = curl_init();
        //$this->proxy = '75.126.26.180:80';
        curl_setopt($process, CURLOPT_URL, $url);
        curl_setopt($process, CURLOPT_AUTOREFERER, 1);
        if(is_array($this->headers)){
            curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers);
        }
        curl_setopt($process, CURLOPT_USERAGENT, $this->user_agent);
        if(isset($this->cookie_file)){            
            if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
            if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
        }
        curl_setopt($process, CURLOPT_ENCODING, $this->compression);
		curl_setopt($process, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($process, CURLOPT_TIMEOUT, 30);
        curl_setopt($process, CURLOPT_FOLLOWLOCATION, 5);
        curl_setopt($process, CURLOPT_SSL_VERIFYPEER, 0);
        if ($this->proxy) curl_setopt($process, CURLOPT_PROXY, $this->proxy);
        if ($this->proxy) curl_setopt($process, CURLOPT_PROXYAUTH, $this->proxyAuther);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
        $return = curl_exec($process);
        $this->respHead = curl_getinfo($process);
        //print_r(curl_getinfo($process));
        //print curl_getinfo($process,CURLINFO_HEADER_SIZE);
        //print curl_getinfo($process,CURLINFO_EFFECTIVE_URL);
        curl_close($process);
        return $return;
    }


    public function post($url, $data, $refer = '')
    {
        $process = curl_init($url);
        curl_setopt($process, CURLOPT_AUTOREFERER, 1);
        if(is_array($this->headers)){
            curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers);
        }
        
        curl_setopt($process, CURLOPT_USERAGENT, $this->user_agent);

        if(isset($this->cookie_file)){            
            if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
            if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
        }
         
        curl_setopt($process, CURLOPT_ENCODING, $this->compression);
		curl_setopt($process, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($process, CURLOPT_TIMEOUT, 30);
        if ($this->proxy) curl_setopt($process, CURLOPT_PROXY, $this->proxy);
        if ($this->proxy) curl_setopt($process, CURLOPT_PROXYAUTH, $this->proxyAuther);
        curl_setopt($process, CURLOPT_POSTFIELDS, $data);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($process, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($process, CURLOPT_FOLLOWLOCATION, 5);
        // curl_setopt($process, CURLOPT_POST, 1);
        $return = curl_exec($process);
        $this->respHead = curl_getinfo($process);
        curl_close($process);
        return $return;
    }
    
    public function bakunn_post($url, $data, $refer = '')
    {
        $process = curl_init($url);
        curl_setopt($process, CURLOPT_AUTOREFERER, 1);
        if(is_array($this->headers)){
            curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers);
        }
        
        curl_setopt($process, CURLOPT_USERAGENT, $this->user_agent);

        if(isset($this->cookie_file)){            
            if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
            if ($this->cookies == TRUE) curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
        }
         
        curl_setopt($process, CURLOPT_ENCODING, $this->compression);
		curl_setopt($process, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($process, CURLOPT_TIMEOUT, 30);
        if ($this->proxy) curl_setopt($process, CURLOPT_PROXY, $this->proxy);
        if ($this->proxy) curl_setopt($process, CURLOPT_PROXYAUTH, $this->proxyAuther);
        curl_setopt($process, CURLOPT_POSTFIELDS, $data);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($process, CURLOPT_HEADER, 1);
        curl_setopt($process, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($process, CURLOPT_FOLLOWLOCATION, 5);
        // curl_setopt($process, CURLOPT_POST, 1);
        $return = curl_exec($process);
        $this->respHead = curl_getinfo($process);
        curl_close($process);
        return $return;
    }

    public function put($url, $content)
    {
        $process = curl_init($url);

        if(isset($this->cookie_file)){            
            curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
            curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
        }
        
        if(is_array($this->headers)){
            curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers);
        }
        curl_setopt($process, CURLOPT_HEADER, true);
        curl_setopt($process, CURLOPT_TIMEOUT, 200);
        curl_setopt($process, CURLOPT_POSTFIELDS, $content);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($process, CURLOPT_CUSTOMREQUEST, 'PUT');
        $return = curl_exec($process);
        curl_close($process);
        return $return;
    }

    public function delete($url, $content)
    {
        $process = curl_init($url);
        
        if(isset($this->cookie_file)){            
            curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
            curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
        }
        if(is_array($this->headers)){
            curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers);
        }
        curl_setopt($process, CURLOPT_HEADER, true);
        curl_setopt($process, CURLOPT_TIMEOUT, 200);
        curl_setopt($process, CURLOPT_POSTFIELDS, $content);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($process, CURLOPT_CUSTOMREQUEST, 'DELETE');
        $return = curl_exec($process);
        curl_close($process);
        return $return;
    }

    public function patch($url, $content)
    {
        $process = curl_init($url);
        
        if(isset($this->cookie_file)){            
            curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
            curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
        }
        if(is_array($this->headers)){
            curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers);
        }
        curl_setopt($process, CURLOPT_HEADER, true);
        curl_setopt($process, CURLOPT_TIMEOUT, 200);
        curl_setopt($process, CURLOPT_POSTFIELDS, $content);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($process, CURLOPT_CUSTOMREQUEST, 'PATCH');
        $return = curl_exec($process);
        curl_close($process);
        return $return;
    }

    public function error($error)
    {
        echo "<center><div style='width:500px;border: 3px solid #FFEEFF; padding: 3px; background-color: #FFDDFF;font-family: verdana; font-size: 10px'><b>cURL Error</b><br>$error</div></center>";
        die;
    }
}
