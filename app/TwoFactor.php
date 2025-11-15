<?php
/**
 * Simple TOTP (Time-Based One-Time Password) implementation
 * Compatible with Google Authenticator, Authy, etc.
 */
class TwoFactor {
    
    /**
     * Generate a random secret key (Base32 encoded)
     */
    public static function generateSecret($length = 16) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // Base32 alphabet
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }
        return $secret;
    }
    
    /**
     * Verify a TOTP code
     */
    public static function verifyCode($secret, $code, $discrepancy = 1) {
        $currentTime = time();
        
        // Check current time slot and adjacent ones for clock drift
        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $calculatedCode = self::getCode($secret, $currentTime + ($i * 30));
            if ($calculatedCode === $code) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Get the current TOTP code
     */
    public static function getCode($secret, $time = null) {
        if ($time === null) {
            $time = time();
        }
        
        $timeSlice = floor($time / 30);
        $secretKey = self::base32Decode($secret);
        
        // Pack time into binary string
        $time = pack('N*', 0) . pack('N*', $timeSlice);
        
        // Hash time with secret key
        $hash = hash_hmac('sha1', $time, $secretKey, true);
        
        // Extract dynamic binary code
        $offset = ord($hash[19]) & 0x0F;
        $code = (
            ((ord($hash[$offset + 0]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % 1000000;
        
        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Get OTPAuth URL for Google Authenticator
     */
    public static function getOTPAuthUrl($secret, $email, $issuer = 'WharfStats') {
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s',
            rawurlencode($issuer),
            rawurlencode($email),
            $secret,
            rawurlencode($issuer)
        );
    }
    
    /**
     * Get QR Code URL for displaying in img tag
     */
    public static function getQRCodeUrl($secret, $email, $issuer = 'WharfStats') {
        $otpauthUrl = self::getOTPAuthUrl($secret, $email, $issuer);
        return '/app/qr-code.php?data=' . urlencode($otpauthUrl);
    }
    
    /**
     * Generate backup codes
     */
    public static function generateBackupCodes($count = 10) {
        $codes = [];
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        
        for ($i = 0; $i < $count; $i++) {
            $code = '';
            for ($j = 0; $j < 8; $j++) {
                $code .= $chars[random_int(0, 35)];
            }
            $codes[] = $code;
        }
        
        return $codes;
    }
    
    /**
     * Base32 decode (RFC 4648)
     */
    private static function base32Decode($secret) {
        $secret = strtoupper($secret);
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $decoded = '';
        
        $secret = str_replace('=', '', $secret);
        $buffer = 0;
        $bitsLeft = 0;
        
        for ($i = 0; $i < strlen($secret); $i++) {
            $val = strpos($alphabet, $secret[$i]);
            if ($val === false) {
                continue;
            }
            
            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;
            
            if ($bitsLeft >= 8) {
                $decoded .= chr(($buffer >> ($bitsLeft - 8)) & 0xFF);
                $bitsLeft -= 8;
            }
        }
        
        return $decoded;
    }
}
