<?php

namespace App\Modules\admission\Services;

use DomainException;

final class AdmissionCredentialCipher
{
    private const KEY_BYTES=32;
    private const IV_BYTES=12;
    private const TAG_BYTES=16;
    private string $key;

    public function __construct(?string $encodedKey=null,?string $keyFile=null)
    {
        $encodedKey=$encodedKey??trim((string)env('admission.credentialsKey',''));
        $this->key=$encodedKey!==''?$this->decodeKey($encodedKey):$this->fileKey($keyFile??WRITEPATH.'keys'.DIRECTORY_SEPARATOR.'admission-payment.key');
    }

    public function encrypt(?string $value): ?string
    {
        if($value===null||$value==='')return null;$iv=random_bytes(self::IV_BYTES);$tag='';$cipher=openssl_encrypt($value,'aes-256-gcm',$this->key,OPENSSL_RAW_DATA,$iv,$tag);if($cipher===false)throw new DomainException('Admission credential encryption failed.');return 'v1:'.base64_encode($iv.$tag.$cipher);
    }

    public function decrypt(?string $value): ?string
    {
        if($value===null||$value==='')return null;if(!str_starts_with($value,'v1:'))throw new DomainException('Unsupported encrypted credential format.');$decoded=base64_decode(substr($value,3),true);if($decoded===false||strlen($decoded)<=self::IV_BYTES+self::TAG_BYTES)throw new DomainException('Invalid encrypted credential.');$iv=substr($decoded,0,self::IV_BYTES);$tag=substr($decoded,self::IV_BYTES,self::TAG_BYTES);$cipher=substr($decoded,self::IV_BYTES+self::TAG_BYTES);$plain=openssl_decrypt($cipher,'aes-256-gcm',$this->key,OPENSSL_RAW_DATA,$iv,$tag);if($plain===false)throw new DomainException('Admission payment credentials could not be decrypted.');return $plain;
    }

    private function decodeKey(string $encoded): string
    {
        $key=base64_decode($encoded,true);if($key===false||strlen($key)!==self::KEY_BYTES)throw new DomainException('admission.credentialsKey must be a base64-encoded 32-byte key.');return $key;
    }

    private function fileKey(string $path): string
    {
        $directory=dirname($path);if(!is_dir($directory)&&!mkdir($directory,0700,true)&&!is_dir($directory))throw new DomainException('Unable to create the Admission credential key directory.');
        if(!is_file($path)){$handle=@fopen($path,'x');if($handle){fwrite($handle,base64_encode(random_bytes(self::KEY_BYTES)));fclose($handle);@chmod($path,0600);}}
        $encoded=is_file($path)?trim((string)file_get_contents($path)):'';return $this->decodeKey($encoded);
    }
}
