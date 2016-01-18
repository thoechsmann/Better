<?
class Property  {

    protected $name;

    public function __construct($name) {
        $this->name = $name;
    }
}

class PropertyInteger extends Property  {

    public function Register($value = 0)
    {
        $this->RegisterPropertyInteger($this->name, $value);
    }

    public function Value()
    {
        return $this->ReadPropertyInteger($this->name);
    }

}

class PropertyString extends Property  {

    public function Register($value = "")
    {
        $this->RegisterPropertyString($this->name, $value);
    }

    public function Value()
    {
        return $this->ReadPropertyString($this->name);
    }

}

class PropertyIntegerIndexed extends PropertyInteger  {

    public function __construct($name, $indexName, $index) {
        parent::__construct();
        $this->name = $indexName . $index . $name;
    }

}

class PropertyStringIndexed extends PropertyString  {

    public function __construct($name, $indexName, $index) {
        parent::__construct();
        $this->name = $indexName . $index . $name;
    }

}

class PropertyArray {

    protected $indexName;
    protected $count;
    protected $properties = array();

    public function __construct($name, $indexName, $count) {
        parent::__construct();
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

    public function __construct($name, $indexName, $count) {
        parent::__construct($name, $indexName, $count);

        for($i = 0; $i<$count; $i++)
        {
            $this->$properties[0] = new PropertyIntegerIndexed($name, $indexName, $i);
        }       
    }

}

class PropertyArrayString extends PropertyArray  {

    public function __construct($name, $indexName, $count) {
        parent::__construct($name, $indexName, $count);

        for($i = 0; $i<$count; $i++)
        {
            $this->$properties[0] = new PropertyStringIndexed($name, $indexName, $i);
        }       
    }

}

?>