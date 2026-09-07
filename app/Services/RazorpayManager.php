<?php

namespace App\Services;

use App\Models\Company;
use Exception;

/**
 * Resolves Razorpay credentials and returns a ready RazorpayService.
 *
 *   • Platform → config('services.razorpay.*')   (tenant → platform subscriptions)
 *   • Tenant   → per-company get_setting()        (customer → tenant orders, later)
 *
 * Usage:
 *   RazorpayManager::forPlatform()->createOrder(...)
 *   RazorpayManager::forCompany($company)->verifySignature(...)
 *   RazorpayManager::forCurrentTenant()->createOrder(...)
 */
class RazorpayManager
{
    /** Built services cached per context. */
    protected static array $instances = [];

    /** Platform (super-admin) gateway — subscription payments. */
    public static function forPlatform(): RazorpayService
    {
        return static::$instances['platform'] ??= new RazorpayService(
            (string) config('services.razorpay.key_id'),
            (string) config('services.razorpay.key_secret')
        );
    }

    /** A specific tenant company's gateway — keys from company settings. */
    public static function forCompany(Company $company): RazorpayService
    {
        $key = 'company_'.$company->id;

        return static::$instances[$key] ??= new RazorpayService(
            (string) get_setting('razorpay_key_id', '', $company->id),
            (string) get_setting('razorpay_key_secret', '', $company->id)
        );
    }

    /** The current resolved tenant's gateway. */
    public static function forCurrentTenant(): RazorpayService
    {
        $company = tenant();

        if (! $company) {
            throw new Exception('No tenant resolved for Razorpay.');
        }

        return static::forCompany($company);
    }

    /** Public key id for the platform checkout modal. */
    public static function platformKeyId(): string
    {
        return (string) config('services.razorpay.key_id');
    }

    /** Platform webhook secret. */
    public static function platformWebhookSecret(): ?string
    {
        return config('services.razorpay.webhook_secret');
    }
}