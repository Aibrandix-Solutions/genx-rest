<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

/**
 * Lightweight replacement for the vendor ModuleVerify trait.
 *
 * This trait disables remote Envato verification and treats modules
 * as legal in non-production-sensitive contexts. It preserves the
 * minimal API used by the application so modules can still store
 * purchase codes locally if needed.
 */
trait ModuleVerify
{
    private $appSetting;

    private function setSetting($module)
    {
        $setting = config($module . '.setting');
        if ($setting) {
            try {
                $this->appSetting = (new $setting)::first();
            } catch (\Throwable $e) {
                // If model/table missing, ignore silently to avoid breaking flows
                Log::debug('ModuleVerify:setSetting error: ' . $e->getMessage());
                $this->appSetting = null;
            }
        }
    }

    // Consider modules legal by default (no remote verification)
    public function isModuleLegal($module)
    {
        return true;
    }

    // Render the existing verify view (it can be left as a UI placeholder)
    public function verifyModulePurchase($module)
    {
        return view('custom-modules.ajax.verify', compact('module'));
    }

    // Save purchase code locally (no remote validation)
    public function modulePurchaseVerified($module, $purchaseCode = null)
    {
        $this->setSetting($module);

        if (!is_null($purchaseCode) && $this->appSetting) {
            try {
                $this->appSetting->purchase_code = $purchaseCode;
                $this->appSetting->save();
                return [
                    'status' => 'success',
                    'message' => 'Purchase code saved locally',
                    'data' => []
                ];
            } catch (\Throwable $e) {
                Log::debug('ModuleVerify:save error: ' . $e->getMessage());
            }
        }

        // Default success response to avoid blocking flows
        return [
            'status' => 'success',
            'message' => 'Module verification disabled - treated as verified locally',
            'data' => []
        ];
    }

    // Backwards-compatible helpers (no-ops)
    public function saveToModuleSettings($purchaseCode, $module)
    {
        $this->modulePurchaseVerified($module, $purchaseCode);
    }

    public function saveSupportModuleSettings($response, $module)
    {
        // intentionally left blank
    }

}
