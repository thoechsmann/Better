<?
class Variable  {

    private $ident;
    private $module;
    private $id = false;

    public function __construct($module, $ident) {
        $this->module = $module;
        $this->ident = $ident;
    }

    public function RegisterVariableInteger($name, $profile = "", $position = 0)
    {
        $id = $this->module->RegisterVariableInteger($this->ident, $name, $profile, $position);
        IPS_SetName($id, $name);
    }

    public function RegisterVariableBoolean($name, $profile = "", $position = 0)
    {
        $id = $this->module->RegisterVariableBoolean($this->ident, $name, $profile, $position);
        IPS_SetName($id, $name);
    }

    public function RegisterVariableString($name, $profile = "", $position = 0)
    {
        $id = $this->module->RegisterVariableString($this->ident, $name, $profile, $position);
        IPS_SetName($id, $name);
    }

    public function Ident()
    {
        return $this->ident;
    }

    public function Id()
    {
        if($this->id === false)
        {
            $this->id = $this->module->GetIDForIdent($this->ident);

            if($this->id === false)
            {
                throw new Exception("Variable::Id() - Ident " . $this->ident . " not found.");
            }
        }

        return $this->id;
    }

    public function SetHidden($hide)
    {
        IPS_SetHidden($this->Id(), $hide);
    }

    public function EnableAction()
    {
        $this->module->EnableAction($this->ident);
    }

    public function GetValue()
    {        
        return GetValue($this->Id());
    }

    public function SetValue($value)
    {
        SetValue($this->Id(), $value);
    }

    public function SetPosition($pos)
    {
        IPS_SetPosition($this->Id(), $pos);
    }
}

class VariableArray
{
    const Delimiter = "_";

    private $module;
    private $prefix;
    private $sizes = array();
    private $is2D;    

    public function __construct($module, $prefix, $size1, $size2 = 0) {
        $this->module = $module;        

        $this->prefix = $prefix;
        $this->sizes[0] = $size1;
        $this->sizes[1] = $size2;

        if(!is_numeric($size1))
            throw new Exception("VariableArray::__construct - size1 is not a number!");

        if(!is_numeric($size2))
            throw new Exception("VariableArray::__construct - size2 is not a number!");

        if($prefix == "")
            throw new Exception("VariableArray::__construct - prefix not set!");

        $this->is2D = $size2 != 0;
    }

    public function At($index1, $index2 = false)
    {
        if($index2 === false && $this->is2D)
            throw new Exception("VariableArray::At(index1) - initialized as 2D array. 2nd index required!");            

        if(is_numeric($index2) && !$this->is2D)
            throw new Exception("VariableArray::At(index1, index2) - not initialized as 2D array, but 2nd index provided!");            

        $this->CheckPositionBounds(0, $index1);
    
        if($index2 !== false)
            $this->CheckPositionBounds(1, $index2);

        if($this->is2D)
        {
            return new Variable($this->module, $this->prefix . $index1 . self::Delimiter . $index2);
        }
        else
        {
            return new Variable($this->module, $this->prefix . $index1);
        }
    }

    public function GetIndexForIdent($otherIdent)
    {
        return $this->IndexForIdent($otherIdent, 0);
    }

    public function GetIndex2ForIdent($otherIdent)
    {
        return $this->IndexForIdent($otherIdent, 1);
    }

    private function CheckPositionBounds($index, $pos)
    {
        if($pos >= $this->sizes[$index])
        {
            throw new Exception(
                "VariableArray::CheckPositionBounds(" . $index . 
                ", " . $pos . 
                ") - Position out of bounds. (Size: " . $this->sizes[$index] .
                ")");
        }
    }

    private function IndexForIdent($otherIdent, $indexNumber)
    {
        $indexes = array();
        $prefixLen = strlen($this->prefix);
        $delimiterLen = strlen(self::Delimiter);
        $size1Len = strlen((string)$this->sizes[0]);
        $size2Len = strlen((string)$this->sizes[1]);

        if($this->is2D)
            $completeLen = $prefixLen + $delimiterLen + $size1Len + $size2Len;
        else
            $completeLen = $prefixLen + $size1Len;

        if(strlen($otherIdent) != $completeLen)
            return false;

        // index 1
        $pos = 0;
        $otherPrefix1 = substr($otherIdent, $pos, $prefixLen);
        $pos += $prefixLen;

        if($otherPrefix1 != $this->prefix)
            return false;

        $indexes[0] = substr($otherIdent, $pos, $size1Len);
        $pos += $size1Len;

        if(!is_numeric($indexes[0]))
            return false;

        if($indexes[0] >= $this->sizes[0])
            return false;

        // index 2
        if($this->is2D)
        {
            $otherPrefix2 = substr($otherIdent, $pos, $delimiterLen);
            $pos += $delimiterLen;

            if($otherPrefix2 != self::Delimiter)
                return false;

            $indexes[1] = substr($otherIdent, $pos, $size2Len);

            if(!is_numeric($indexes[1]))
                return false;

            if($indexes[1] >= $this->sizes[1])
                return false;
        }

        return $indexes[$indexNumber];
    }

}

?>