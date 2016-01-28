<?
class Property  {

    protected $name;
    protected $module;

    public function __construct($module, $name) {
        $this->module = $module;
        $this->name = $name;
    }

    public function Name()
    {
        return $this->name;
    }

    public function SetValue($value)
    {
        IPS_SetProperty($this->module->InstanceId(), $this->name, $value);
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

    public function __construct($module, $name, $index) {
        parent::__construct($module, $name . $index);
    }

}

class PropertyStringIndexed extends PropertyString  {

    public function __construct($module, $name, $index) {
        parent::__construct($module, $name . $index);
    }

}

class PropertyArray {

    protected $module;
    protected $count;
    protected $properties = array();

    public function __construct($module, $name, $count) {
        $this->module = $module;
        $this->name = $name;
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

    public function NameAt($index)
    {
        return $this->properties[$index]->Name();
    }
}

class PropertyArrayInteger extends PropertyArray  {

    public function __construct($module, $name, $count) {
        parent::__construct($module, $name, $count);

        for($i = 0; $i<$count; $i++)
        {
            $this->properties[$i] = new PropertyIntegerIndexed($this->module, $name, $i);
        }       
    }

}

class PropertyArrayString extends PropertyArray  {

    public function __construct($module, $name,  $count) {
        parent::__construct($module, $name, $count);

        for($i = 0; $i<$count; $i++)
        {
            $this->properties[$i] = new PropertyStringIndexed($this->module, $name, $i);
        }       
    }

}

?>