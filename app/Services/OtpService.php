<?php

namespace App\Services;

class OtpService
{
    public static function generateOtp(string $otpSeed): string
    {
        // Get current Unix timestamp
        $timestamp = time();
        
        // Calculate number of 30-second intervals since Unix epoch
        $intervals = floor($timestamp / 30);
        
        // Generate HMAC-SHA1 hash
        $hash = hash_hmac(
            'sha1',
            pack('N*', 0) . pack('N*', $intervals),
            static::base32Decode($otpSeed),
            true
        );
        
        // Get offset
        $offset = ord($hash[strlen($hash) - 1]) & 0xf;
        
        // Generate 4-byte code
        $code = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;
        
        // Pad with leading zeros if necessary
        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }
    
    private static function base32Decode(string $input): string
    {
        $map = [
            'A' => 0, 'B' => 1, 'C' => 2, 'D' => 3, 'E' => 4, 'F' => 5, 'G' => 6, 'H' => 7,
            'I' => 8, 'J' => 9, 'K' => 10, 'L' => 11, 'M' => 12, 'N' => 13, 'O' => 14, 'P' => 15,
            'Q' => 16, 'R' => 17, 'S' => 18, 'T' => 19, 'U' => 20, 'V' => 21, 'W' => 22, 'X' => 23,
            'Y' => 24, 'Z' => 25, '2' => 26, '3' => 27, '4' => 28, '5' => 29, '6' => 30, '7' => 31
        ];
        
        // Remove padding and convert to uppercase
        $input = rtrim(strtoupper($input), '=');
        
        $binary = '';
        $length = strlen($input);
        $blocks = ceil($length / 8) * 8;
        
        // Pad input to multiple of 8
        $input = str_pad($input, $blocks, '=');
        
        // Process input in blocks of 8 characters
        for ($i = 0; $i < $blocks; $i += 8) {
            $chunk = substr($input, $i, 8);
            if (strpos($chunk, '=') !== false) {
                $chunk = substr($chunk, 0, strpos($chunk, '='));
            }
            
            $buffer = 0;
            $bufferLength = 0;
            
            // Convert each character to 5 bits
            for ($j = 0; $j < strlen($chunk); $j++) {
                $buffer = ($buffer << 5) | $map[$chunk[$j]];
                $bufferLength += 5;
                
                // Extract complete bytes
                while ($bufferLength >= 8) {
                    $bufferLength -= 8;
                    $binary .= chr(($buffer >> $bufferLength) & 0xFF);
                }
            }
        }
        
        return $binary;
    }
}
