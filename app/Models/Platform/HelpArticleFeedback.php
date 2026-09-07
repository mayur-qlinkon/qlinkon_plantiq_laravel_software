<?php

namespace App\Models\Platform;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HelpArticleFeedback extends Model
{
    use HasFactory;

    protected $table = 'help_article_feedback';

    protected $fillable = [
        'help_article_id',
        'user_id',
        'is_helpful',
        'ip_address',
    ];

    protected $casts = [
        'is_helpful' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function article()
    {
        return $this->belongsTo(HelpArticle::class, 'help_article_id');
    }
     public function user()
    {
        return $this->belongsTo(User::class);
    }
}