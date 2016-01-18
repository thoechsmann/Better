<?
class Property  {

    protected $name;
    protected $module;

    public function __construct($module, $name) {
        $this->module = $module;
        $this->name = $name;
    }
}

class PropertyInteger extends Property  {

    public function Register($value = 0)
    {
        $this->module->RegisterPropertyInteger($this->name, $value);
    }

    public function Value()
    {
        return $this->module->ReadPropertyInteger($this->name);
    }

}

class PropertyString extends Property  {

    public function Register($value = "")
    {
        $this->module->RegisterPropertyString($this->name, $value);
    }

    public function Value()
    {
        return $this->module->ReadPropertyString($this->name);
    }

}

class PropertyIntegerIndexed extends PropertyInteger  {

    public function __construct($module, $name, $indexName, $index) {
        parent::__construct($module);
        $this->name = $indexName . $index . $name;
    }

}

class PropertyStringIndexed extends PropertyString  {

    public function __construct($module, $name, $indexName, $index) {
        parent::__construct($module);
        $this->name = $indexName . $index . $name;
    }

}

class PropertyArray {

    protected $module;
    protected $indexName;
    protected $count;
    protected $properties = array();

    public function __construct($module, $name, $indexName, $count) {
        $this->module = $module;
        $this->name = $name;
        $this->indexName = $indexName;
        $this->count = $count; 
    }

    public function RegisterAll($value = 0)
    {
        for($i = 0; $i<$count; $i++)
        {
            $this->$properties[0]->Register($value);
        }       
    }

    public function At($index)
    {
        return $this->$properties[$index];
    }

    public function ValueAt($index)
    {
        return $this->$properties[$index]->Value();
    }

}

class PropertyArrayInteger extends PropertyArray  {

    public function __construct($module, $name, $indexName, $count) {
        parent::__construct($module, $name, $indexName, $count);

        for($i = 0; $i<$count; $i++)
        {
            $this->properties[0] = new PropertyIntegerIndexed($this->module, $name, $indexName, $i);
        }       
    }

}

class PropertyArrayString extends PropertyArray  {

    public function __construct($module, $name, $indexName, $count) {
        parent::__construct($module, $name, $indexName, $count);

        for($i = 0; $i<$count; $i++)
        {
            $this->properties[0] = new PropertyStringIndexed($this->module, $name, $indexName, $i);
        }       
    }

}

?>