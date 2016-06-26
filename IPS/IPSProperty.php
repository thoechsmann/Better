<?
abstract class IPSProperty  {
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

    public abstract function Value();
    public abstract function Register($value);
}

class IPSPropertyInteger extends Property  {

    public function Register($value = 0)
    {
        // IPS_LogMessage("PropertyInteger", "Registering property: " . $this->name . " = " . $value);
        $this->module->RegisterPropertyInteger($this->name, $value);
    }

    public function Value()
    {
        return $this->module->ReadPropertyInteger($this->name);
    }

}

class IPSPropertyString extends Property  {

    public function Register($value = "")
    {
        // IPS_LogMessage("PropertyString", "Registering property: " . $this->name . " = " . $value);
        $this->module->RegisterPropertyString($this->name, $value);
    }

    public function Value()
    {
        return $this->module->ReadPropertyString($this->name);
    }

}

abstract class PropertyArray {
    protected $module;
    protected $count;
    protected $properties = array();

    public function __construct($module, $name, $count) {
        $this->module = $module;
        $this->name = $name;
        $this->count = $count; 

        for($i = 0; $i<$count; $i++)
        {
            $this->properties[$i] = static::CreateProperty($name . $i);
        }       
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

    abstract protected function CreateProperty($name);
}

class PropertyArrayInteger extends PropertyArray  {
    protected function CreateProperty($name)
    {
        return new IPSPropertyInteger($this->module, $name);
    }
}

class PropertyArrayString extends PropertyArray  {
    protected function CreateProperty($name)
    {
        return new IPSPropertyString($this->module, $name);
    }
}

?>