<?php
namespace App\Repositories;

use Exception;
use Illuminate\Container\Container as Application;
use Illuminate\Database\Eloquent\Model;
abstract class BaseAPIRepository{
/**
     * @var Model
     */
    protected $model;
    /**
     * @var Application
     */
    protected $app;
    /**
     * @param Application $app
     *
     * @throws \Exception
     */
    protected $with=[];


    protected $oderColumns = [];
    protected $orderName = 'DESC';

    protected $betweenQueryCallBacks=[];
    protected $whereInCallBacks=[];
    protected $callBacks=[];
    protected $likeCallBacks=[];
    protected $globalQueryCallback=null;
    protected $globalWiths = [];
    private $textGlobalFilter = null;


    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->makeModel($this->with);
    }
    /**
     * Get searchable fields array
     *
     * @return array
     */
    abstract public function getFieldsSearchable();
    /**
     * Configure the Model
     *
     * @return string
     */
    abstract public function model();

    public function getFieldsLikeable(){
        return [];
    }

    /**
     * Make Model instance
     *
     * @throws \Exception
     *
     * @return Model
     */
    public function makeModel($withsParameter=[])
    {
        $model = $this->app->make($this->model(),$withsParameter);
        if (!$model instanceof Model) {
            throw new \Exception("Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model");
        }
        return $this->model = $model;
    }

    /**
     * Paginate records for scaffold.
     *
     * @param int $perPage
     * @param array $columns
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage, $columns = ['*'])
    {
        $query = $this->allQuery();
        return $query->paginate($perPage, $columns);
    }
    /**
     * Build a query for retrieving all records.
     *
     * @param array $search
     * @param int|null $skip
     * @param int|null $limit
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function allQuery($search = [], $skip = null, $limit = null)
    {
        $query = $this->basicQuery();


        if (count($search)) {
            foreach($search as $key => $value) {
                if (in_array($key, $this->getFieldsSearchable())) {
                    $query->where($key, $value);
                }
            }
        }
        if (!is_null($skip)) {
            $query->skip($skip);
        }
        if (!is_null($limit)) {
            $query->limit($limit);
        }
        if(count($this->betweenQueryCallBacks)>0){
            foreach ($this->betweenQueryCallBacks as $key => $value) {
                $callBack=$this->betweenQueryCallBacks[$key];
                $callBack($query);
            }
        }
        if(count($this->whereInCallBacks)>0){
            foreach ($this->whereInCallBacks as $key => $value) {
                $callBack=$this->whereInCallBacks[$key];
                $callBack($query);
            }
        }
        if(count($this->callBacks)>0){
            foreach ($this->callBacks as $key => $value) {
                $callBack=$this->callBacks[$key];
                $callBack($query);
            }
        }
        if(count($this->likeCallBacks)>0){
            foreach ($this->likeCallBacks as $key => $value) {
                $callBack=$this->likeCallBacks[$key];
                $callBack($query);
            }
        }

        foreach ($this->oderColumns as  $key => $column) {
            $query->orderBy($key,$column);
        }

        if($this->textGlobalFilter!=null){
            $columns = $this->getFieldsLikeable();
            $text = $this->textGlobalFilter;
            $query->where(function($query)use($columns,$text){
                for ($i=0; $i < count($columns); $i++) {
                    $query->orWhere($columns[$i],'like',$text.'%');

                }
            });
        }

        return $query;
    }
    /**
     * Retrieve all records with given filter criteria
     *
     * @param array $search
     * @param int|null $skip
     * @param int|null $limit
     * @param array $columns
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function all($search = [], $skip = null, $limit = null, $columns = ['*'])
    {
        $query = $this->allQuery($search, $skip, $limit);
        return $query->get($columns);
    }

    /**
     * count all raw with all filters
     *
     * @return void
     */
    public function allCount($search=[]){
        $query = $this->allQuery($search,null,null);
        return $query->get([\DB::raw('COUNT(*) as cantidad')])->first()->cantidad;
    }
    /**
     * Create model record
     *
     * @param array $input
     *
     * @return Model
     */
    public function create($input)
    {
        $model = $this->model->newInstance($input);
        $model->save();
        return $model;
    }
    /**
     * Find model record for given id
     *
     * @param int $id
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|Model|null
     */
    public function find($id, $columns = ['*'])
    {
        $query = $this->basicQuery();

        if(count($this->globalWiths)>0){
            foreach ($this->globalWiths as $key => $value) {
                $callBack=$this->globalWiths[$key];
                $callBack($query);
            }
        }
        return $query->find($id, $columns);
    }
    /**
     * Update model record for given id
     *
     * @param array $input
     * @param int $id
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|Model
     */
    public function update($input, $id)
    {
        $query = $this->basicQuery();
        $model = $query->findOrFail($id);
        $model->fill($input);
        $model->save();
        return $model;
    }
    /**
     * @param int $id
     *
     * @throws \Exception
     *
     * @return bool|mixed|null
     */
    public function delete($id)
    {
        $query = $this->basicQuery();
        $model = $query->findOrFail($id);
        return $model->delete();
    }
    /**
     * queryCallBack function
     *
     * @param callable $callBack
     * @return void
     */
    public function addBetweenQueryCallBack($nameColumn,$callBack){
        $this->queryCallBack=$callBack;
        $this->betweenQueryCallBacks[$nameColumn]=$callBack;
    }
    /**
     * queryCallBack function
     *
     * @param callable $callBack
     * @return void
     */
    protected function addCallBack($nameColumn,$callBack){
        $this->callBacks[$nameColumn]=$callBack;
    }
    /**
     * queryCallBack function
     *
     * @param callable $callBack
     * @return void
     */
    protected function addLike($nameColumn,$callBack){
        $this->likeCallBacks[$nameColumn]=$callBack;
    }
    /**
     * queryCallBack function
     *
     * @param callable $callBack
     * @return void
     */
    protected function addCallBackWhereIn($nameColumn,$callBack){
        $this->whereInCallBacks[$nameColumn]=$callBack;
    }
    protected function basicQuery(){
        return $this->model->newQuery();
    }

    protected function setOrders($arrayColumns){
        $this->oderColumns = $arrayColumns ;
    }

    protected function addWith($relation){

        if(!key_exists($relation,$this->globalWiths)){

            $this->globalWiths[$relation]=function($query)use($relation){

                $query->with($relation);

            };

        }else{
            \Debugbar::info($this->globalWiths);
            \Debugbar::error("se establecio with callback duplicado $relation");
        }

    }

    protected function setGlobalFilterByColumn($columnname,$value){
        if(!in_array($columnname,array_keys($this->callBacks))){
            $this->callBacks[$columnname] = function($query)use($columnname,$value){
                $query->where($columnname,$value);
            };
        }else{
            throw new Exception("ya existe un callback para $columnname");
        }

    }


    protected function setFilterLike($text){

        $this->textGlobalFilter = $text;

    }
}
