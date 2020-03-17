<?php


namespace simpleMenu\model;


use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{

    public $timestamps = false;

    protected $table = 'menu';

    protected $fillable = array('parent_id','title','url','order');

    public function parent()
    {
        return $this->belongsTo('App\Menu', 'parent_id');
    }

    public function children()
    {
        return $this->hasMany('App\Menu', 'parent_id');
    }
}
