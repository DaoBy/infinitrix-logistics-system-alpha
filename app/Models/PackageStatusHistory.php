<?php



namespace App\Models;



use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;



class PackageStatusHistory extends Model

{

    use HasFactory;



    protected $fillable = [

        'package_id',

        'package_transfer_id',

        'status',

        'remarks',

        'updated_by'

    ];



    protected $casts = [

        'updated_at' => 'datetime',

    ];



    public function package(): BelongsTo

    {

        return $this->belongsTo(Package::class);

    }



    public function transfer(): BelongsTo

    {

        return $this->belongsTo(PackageTransfer::class, 'package_transfer_id');

    }



    public function updatedBy(): BelongsTo

    {

        return $this->belongsTo(User::class, 'updated_by');

    }

}