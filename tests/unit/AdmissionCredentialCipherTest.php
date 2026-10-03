<?php

namespace Tests\Unit;

use App\Modules\admission\Services\AdmissionCredentialCipher;
use DomainException;
use PHPUnit\Framework\TestCase;

final class AdmissionCredentialCipherTest extends TestCase
{
    public function testEncryptedCredentialRoundTripDoesNotExposePlaintext(): void
    {
        $cipher=new AdmissionCredentialCipher(base64_encode(str_repeat('k',32)));
        $encrypted=$cipher->encrypt('sk_test_private_value');
        self::assertStringStartsWith('v1:',$encrypted);
        self::assertStringNotContainsString('sk_test_private_value',$encrypted);
        self::assertSame('sk_test_private_value',$cipher->decrypt($encrypted));
    }

    public function testWrongKeyCannotDecryptCredential(): void
    {
        $first=new AdmissionCredentialCipher(base64_encode(str_repeat('a',32)));
        $second=new AdmissionCredentialCipher(base64_encode(str_repeat('b',32)));
        $this->expectException(DomainException::class);
        $second->decrypt($first->encrypt('secret'));
    }
}
