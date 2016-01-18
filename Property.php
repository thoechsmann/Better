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
        IPS_LogMessage("PropertyInteger", "Registering property: " . $this->name . " = " . $value);
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
        IPS_LogMessage("PropertyString", "Registering property: " . $this->name . " = " . $value);
        $this->module->RegisterPropertyString($this->name, $value);
    }

    public function Value()
    {
        return $this->module->ReadPropertyString($this->name);
    }

}

class PropertyIntegerIndexed extends PropertyInteger  {

    public function __construct($module, $name, $indexName, $index) {
        parent::__construct($module, $indexName . $index . $name);
    }

}

class PropertyStringIndexed extends PropertyString  {

    public function __construct($module, $name, $indexName, $index) {
        parent::__construct($module, $indexName . $index . $name);
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

    public function RegisterAll()
    {
        for($i = 0; $i<$this->count; $i++)
        {
            $this->properties[$i]->Register();
        }       
    }

    public function At($index)
    {
        return $this->properties[$index];
    }

    public function ValueAt($index)
    {
        return $this->properties[$index]->Value();
    }

}

class PropertyArrayInteger extends PropertyArray  {

    public function __construct($module, $name, $indexName, $count) {
        parent::__construct($module, $name, $indexName, $count);

        for($i = 0; $i<$count; $i++)
        {
            $this->properties[$i] = new PropertyIntegerIndexed($this->module, $name, $indexName, $i);
        }       
    }

}

class PropertyArrayString extends PropertyArray  {

    public function __construct($module, $name, $indexName, $count) {
        parent::__construct($module, $name, $indexName, $count);

        for($i = 0; $i<$count; $i++)
        {
            $this->properties[$i] = new PropertyStringIndexed($this->module, $name, $indexName, $i);
        }       
    }

}

?>