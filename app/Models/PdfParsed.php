<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PdfParsed extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pdf_parsed';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ip_address',
        'user_agent',
        'full_name',
        'file_name',
        'parsed_data',
        'error_message'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'parsed_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The default values for attributes.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Scope a query to only include successful parses.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */

    /**
     * Scope a query to only include failed parses.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */

    /**
     * Get the parsed data as an array.
     *
     * @return array
     */
    public function getParsedData()
    {
        return $this->parsed_data ?? [];
    }

    /**
     * Get the file extension.
     *
     * @return string|null
     */
    public function getFileExtension()
    {
        return $this->file_name ? pathinfo($this->file_name, PATHINFO_EXTENSION) : null;
    }
}
