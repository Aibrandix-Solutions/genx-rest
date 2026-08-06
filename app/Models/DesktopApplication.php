<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DesktopApplication extends Model
{
    use HasFactory;

    const WINDOWS_FILE_PATH = '/downloads/GenX_Companion_Windows.exe';
    const MAC_FILE_PATH = '/downloads/GenX_Companion_Mac';
    const LINUX_FILE_PATH = '/downloads/GenX_Companion_Linux';

    protected $guarded = ['id'];
    protected $table = 'desktop_applications';

    public function getIsActiveAttribute()
    {
        return $this->windows_file_path || $this->mac_file_path || $this->linux_file_path;
    }
}
