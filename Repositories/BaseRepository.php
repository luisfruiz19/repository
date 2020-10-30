<?php

namespace App\Repositories;

use Illuminate\Container\Container as Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

abstract class BaseRepository
{

    //protected $whereIn;
    protected $scopeCallBacks=[];


    protected $scopeOrderBy=[];

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
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->makeModel();
        $this->setScope(function($query){
            return $query;
        });
    }

    /**
     * Get searchable fields array
     *
     * @return array
     */
    abstract public function getFieldsSearchable();

    /**
     * Get sortable fields array
     *
     * @return array
     */
    abstract public function getFieldsSortable();

    /**
     * Configure the Model
     *
     * @return string
     */
    abstract public function model();

    /**
     * Make Model instance
     *
     * @throws \Exception
     *
     * @return Model
     */
    public function makeModel()
    {
        $model = $this->app->make($this->model());

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
     * @param array $search example [
     *   ['column'=>$value],
     *   ['column',operator,value]
     * ]
     * @param int|null $skip
     * @param int|null $limit
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function allQuery($search = [], $skip = null, $limit = null)
    {
        $query = $this->model->newQuery();

        foreach ($this->scopeCallBacks as $key=>$value) {
            $this->scopeCallBacks[$key]($query);
        }

        foreach ($this->scopeOrderBy as $key=>$value) {
            $this->scopeOrderBy[$key]($query);
        }

        \Debugbar::info($query);
            foreach($search as $key => $value) {
                //*Restriccion para arrays con 3 parametros
                if(count($value)===1){
                    foreach ($value as $keyy => $valuee) {
                        if (in_array($keyy, $this->getFieldsSearchable())) {
                            $query->where($keyy, $valuee);
                        }
                    }
                }elseif(count($value)===3){
                    if (in_array($value[0], $this->getFieldsSearchable())) {
                        $query->where($value[0],$value[1], $value[2]);
                    }
                }
            }

        if (!is_null($skip)) {
            $query->skip($skip);
        }

        if (!is_null($limit)) {
            $query->limit($limit);
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
        $query = $this->model->newQuery();

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
        $query = $this->model->newQuery();

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
        $query = $this->model->newQuery();

        $model = $query->findOrFail($id);

        return $model->delete();
    }

    /**
     * __callStatic intercepta las llamadas a metodos del tipo
     * by{Model::Column}
     *
     * @param mixed $name
     * @param mixed $arguments
     * @return void
     */
    public function __call($name, $arguments)
    {
        $lenght=strlen($name);
        if(substr($name,0,2)=='by'){
            $lenght=strlen($name)-2;
            $column_name=substr($name,2,$lenght);
            return $this->model()::where(Str::snake($column_name,'_'),$arguments)->get();
        }
        return call_user_func_array($name,$arguments);
    }

    public function scope($query){
        $scopeName=$this->scopeCallBack;
        if($this->scopeCallBack==null){
            return $query;
        }
        return $scopeName($query);
    }

    protected function setScope($function){
        $this->scopeCallBack=$function;
    }

    /**
     * addScope callback para agregar verbos a las querys
     *
     * @param mixed $name
     * @param mixed $callbackfunction
     * @return void
     */
    protected function addScope($name,$callbackfunction){
        if(in_array($name,$this->getFieldsSearchable())){
            $this->scopeCallBacks[$name]=$callbackfunction;
        }else{
            \Debugbar::error("el nombre del scope no coincide con ningun campo buscable");
        }
    }

    protected function addRuleOrderBy($columName,$desc=true){
        if(in_array($columName,$this->getFieldsSortable())){
            $this->scopeOrderBy[$columName]=function($query)use($columName,$desc){
                $query->orderBy($columName,$desc?'DESC':'ASC');
            };
        }else{
            \Debugbar::error("el nombre del scope no coincide con ningun campo sortable");
        }
    }

    protected function whereIn($column,array $values){
        $this->addScope($column,function($query) use($column,$values){
            $query->whereIn($column,$values);
        });
    }

    protected function whereLike($column,$value){
        $this->addScope($column,function($query) use($column,$value){
            $query->where($column,'like',$value.'%');
        });
    }
    protected function where($column,$value){
        $this->addScope($column,function($query) use($column,$value){
            $query->where($column,$value);
        });
    }
}
