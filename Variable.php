<?
class IPSObject  {

    private $ident;
    private $id = false;
    protected $parentId;

    public function __construct($parentId, $ident) {
        $this->parentId = $parentId;
        $this->ident = $ident;
    }

    public function __toString()
    {
        return 
        "Ident: " . $this->Ident() .
        " ParentId: " . $this->parentId;
    }

    public function Ident()
    {
        return $this->ident;
    }

    public function Id()
    {
        if($this->id === false)
        {
            $this->id = $this->GetIDForIdent($this->ident);

            if($this->id === false)
            {
                throw new Exception("Variable::Id() - Ident " . $this->ident . " not found.");
            }
        }

        return $this->id;
    }

    protected SetId($id)
    {
        if($this->id !== false)
        {
            $currentId = $this->id;
            throw new Exception("Variable::SetId($id) - Id already set to $currentId. Changing is not allowed.");
        }

        $this->id = $id;
    }

    public function GetIDForIdent($ident)
    {
        $id = @IPS_GetObjectIDByIdent($ident, $this->parentId); 
        
        if($id === false)
            $id = 0;
        
        return $id;
    }  

    public function SetHidden($hide)
    {
        IPS_SetHidden($this->Id(), $hide);
    }

    public function Hide()
    {
        $this->SetHidden(true);
    }

    public function Show()
    {
        $this->SetHidden(false);
    }

    public function SetPosition($pos)
    {
        IPS_SetPosition($this->Id(), $pos);
    }

    public function SetIcon($icon)
    {
        IPS_SetIcon($this->Id(), $icon);
    }
}

class IPSVar extends IPSObject
{
    const TypeBoolean = 0;
    const TypeInteger = 1;
    const TypeFloat = 2;
    const TypeString = 3;

    private $type = false;

    public function __construct($parentId, $ident, $type) {
        parent::__construct($parentId, $ident);
        $this->type = $type;
    }

    public function __toString()
    {
        $typeName = "Not set!";
        switch($this->type)
        {
            case IPSVar::TypeBoolean:
                $typeName = "Boolean";
                break;
            case IPSVar::TypeInteger:
                $typeName = "Integer";
                break;
            case IPSVar::TypeFloat:
                $typeName = "Float";
                break;
            case IPSVar::TypeString:
                $typeName = "String";
                break;
        }

        return parent::__toString() . " type: $typeName";        
    }

    public function GetValue()
    {        
        return GetValue($this->Id());
    }

    public function SetValue($value)
    {
        $this->CheckType($value);
        SetValue($this->Id(), $value);
    }

    private function CheckType($value)
    {
        switch($this->type)
        {
            case IPSVar::TypeBoolean:
                if(!is_bool($value))
                    $typeName = "Booelan";
                break;
            case IPSVar::TypeInteger:
                if(!is_integer($value))
                    $typeName = "Integer";
                break;
            case IPSVar::TypeFloat:
                if(!is_float($value))
                    $typeName = "Float";
                break;
            case IPSVar::TypeString:
                if(!is_string($value))
                    $typeName = "String";
                break;
        }

        if(!empty($typeName))
            IPS_LogMessage("IPSVar - SetValue", "value: $value is not of type $typeName - " . $this);
    }

    public function SetProfile($profile)
    {
        IPS_SetVariableCustomProfile($this->Id(), $profile);
    }

    public function EnableAction() {
        IPS_EnableAction($this->parentId, $this->Ident());
    }
    
    public function DisableAction() {
        IPS_DisableAction($this->parentId, $this->Ident());
    }

    public function Register($name = "", $profile = "", $position = 0) 
    {
        IPS_LogMessage("IPSVar", "Registering var - " . $this);

        if($this->type === false)
        {
            throw new Exception("Type not set.");
        }

        if($name == "")
            $name = $this->Ident();

        if($profile != "") {
            if(!IPS_VariableProfileExists($profile)) {
                throw new Exception("Profile with name ".$profile." does not exist");
            }
        }

        $id = $this->GetIDForIdent($this->Ident());

        if($id > 0) {
            if(!IPS_VariableExists($id))
                throw new Exception("Ident with name ".$this->Ident()." is used for wrong object type"); //bail out
            
            if(IPS_GetVariable($id)["VariableType"] != $this->type) {
                IPS_DeleteVariable($id);
                $id = 0;
            }
        }
        
        if($id == 0)
        {
            $id = IPS_CreateVariable($this->type);

            IPS_SetParent($id, $this->parentId);
            IPS_SetIdent($id, $this->Ident());            
        }

        IPS_SetName($id, $name);        
        IPS_SetPosition($id, $position);
        IPS_SetVariableCustomProfile($id, $profile);
        
        return $id;            
    }
}

class IPSVarBoolean extends IPSVar
{
    public function __construct($parentId, $ident) {
        parent::__construct($parentId, $ident, IPSVar::TypeBoolean);
    }
}

class IPSVarInteger extends IPSVar
{
    public function __construct($parentId, $ident) {
        parent::__construct($parentId, $ident, IPSVar::TypeInteger);
    }
}

class IPSVarFloat extends IPSVar
{
    public function __construct($parentId, $ident) {
        parent::__construct($parentId, $ident, IPSVar::TypeFloat);
    }
}

class IPSVarString extends IPSVar
{
    public function __construct($parentId, $ident) {
        parent::__construct($parentId, $ident, IPSVar::TypeString);
    }
}

class IPSScript extends IPSObject
{
    public function Register($name, $content = "<?\n\n//Autogenerated script\n\n?>", $position = 0) 
    {        
        if($name == "")
            $name = $this->Ident();

        $id = $this->GetIDForIdent($this->Ident());

        if($id == 0)
        {
            $id = IPS_CreateScript(0);
            
            IPS_SetParent($id, $this->parentId);
            IPS_SetIdent($id, $this->Ident());            
        }

        IPS_SetName($id, $name);
        IPS_SetPosition($id, $position);
        IPS_SetScriptContent($id, $content);
        
        return $id;        
    }
}

class IPSLink extends IPSObject
{
    public function Register($tarketId, $name = "", $position = 0) 
    {        
        if($name == "")
            $name = $this->Ident();

        $id = $this->GetIDForIdent($this->Ident());

        if($id == 0)
        {
            $id = IPS_CreateLink();
            
            IPS_SetParent($id, $this->parentId);
            IPS_SetIdent($id, $this->Ident());
        }

        IPS_SetName($id, $name);
        IPS_SetPosition($id, $position);
        IPS_SetLinkTargetID($id, $tarketId);
        
        return $id;        
    }

    public function SetTargetId($targetId)
    {
        IPS_SetLinkTargetID($this->Id(), $targetId);
    }
}


class IPSEvent extends IPSObject
{
    const TypeTrigger = 0;
    const TypeCyclic = 1;
    const TypeScheduler = 2;

    private $type = false;

    public function __construct($parentId, $ident, $type) {
        parent::__construct($parentId, $ident);
        $this->type = $type;
    }

    protected function RegisterEvent($name = "", $position = 0) 
    { 
        IPS_LogMessage("IPSEvent", "Registering event of type " . $this->type . " - $this");

        if($name == "")
            $name = $this->Ident();

        $id = $this->GetIDForIdent($this->Ident());

        if($id != 0 && IPS_GetEvent($id)['EventType'] <> $this->type) 
        {
            IPS_DeleteEvent($id);
            $id = 0;
        }

        if($id == 0)
        {
            $id = IPS_CreateEvent($this->type);
            
            IPS_SetParent($id, $this->parentId);
            IPS_SetIdent($id, $this->Ident());            
        }

        IPS_SetName($id, $name);
        IPS_SetPosition($id, $position);
        
        if (!IPS_EventExists($id)) throw new Exception("Event $ident could not be created."); 

        return $id;
    }

    public function SetScript($content)
    {
        IPS_SetEventScript($this->Id(), "$content;"); 
    }

    public function SetActive($value)
    {
        IPS_SetEventActive($this->Id(), $value);
    }

    public function Activate()
    {
        $this->SetActive(true);        
    }

    public function Deactivate()
    {
        $this->SetActive(false);        
    }

    public function SetLimit($count)
    {
        IPS_SetEventLimit($this->Id(), $count);
    }
}

class IPSEventTrigger extends IPSEvent
{
    const TypeUpdate = 0;
    const TypeChange = 1;
    const TypeBigger = 2;
    const TypeSmaller = 3;
    const TypeValue = 4;

    public function __construct($parentId, $ident) {
        parent::__construct($parentId, $ident, IPSEvent::TypeTrigger);
    }

    public function Register($targetId, $script, $type = IPSEventTrigger::TypeChange, $name = "", $position = 0)
    {
        $id = parent::RegisterEvent($name, $position);
        
        $this->Hide();
        $this->SetScript($script);
        $this->SetTrigger($type, $targetId); 
        $this->SetSubsequentExecution(true);
        $this->Activate();

        return $id;
    }

    public function SetTrigger($type, $targetId)
    {
        IPS_SetEventTrigger($this->Id(), $type, $targetId);
    }

    public function SetSubsequentExecution($value)
    {
        IPS_SetEventTriggerSubsequentExecution($this->Id(), $value);
    }
}

class IPSEventCyclic extends IPSEvent
{
    const DayMonday = 1;
    const DayTuesday = 2;
    const DayWednesday = 4;
    const DayThursday = 8;
    const DayFriday = 16;
    const DaySaturday = 32;
    const DaySunday = 64;

    const DayWeekdays = 31;
    const DayWeekends = 96;
    const DayAll = 127;

    const DateTypeNone = 0;
    const DateTypeOnce = 1;
    const DateTypeDay = 2;
    const DateTypeWeek = 3;
    const DateTypeMonth = 4;
    const DateTypeYear = 5;

    const TimeTypeOnce = 0;
    const TimeTypeSecond = 1;
    const TimeTypeMinute = 2;
    const TimeTypeHour = 3;

    public function __construct($parentId, $ident) {
        parent::__construct($parentId, $ident, IPSEvent::TypeCyclic);
    }

    public function Register($script, $name = "", $position = 0)
    {
        return parent::RegisterEvent($name, $position);
        $this->SetScript($script);
    }

    // Add some nicer functions.
    public SetCyclic($dateType, $dateInterval, $days, $daysInterval, $timeType, $timeInterval)
    {
        IPS_SetEventCyclic($this->Id(), $dateType, $dateInterval, $days, $daysInterval, $timeType, $timeInterval);
    }

    public StartTimer($seconds)
    {
        $this->SetCyclic(self::DateTypeNone, 0, 0, 0, self::TimeTypeSecond, $seconds)
        $this->SetLimit(1);
        $this->Activate();
    }
}

class IPSEventScheduler extends IPSEvent
{
    const DayMonday = 1;
    const DayTuesday = 2;
    const DayWednesday = 4;
    const DayThursday = 8;
    const DayFriday = 16;
    const DaySaturday = 32;
    const DaySunday = 64;

    const DayWeekdays = 31;
    const DayWeekends = 96;
    const DayAll = 127;

    public function __construct($parentId, $ident) {
        parent::__construct($parentId, $ident, IPSEvent::TypeScheduler);
    }

    public function Register($name = "", $position = 0)
    {
        return parent::RegisterEvent($name, $position);
    }
    
    public function SetGroup($groupId, $days)
    {
        IPS_SetEventScheduleGroup($this->Id(), $groupId, $days);
    }

    public function SetGroupPoint($groupId, $pointId, $hour, $minute, $second, $actionId)
    {
        IPS_SetEventScheduleGroupPoint($this->Id(), $groupId, $pointId, $hour, $minute, $second, $actionId);
    }

    public function SetAction($actionId, $name, $color, $scriptContent)
    {
        IPS_SetEventScheduleAction($this->Id(), $actionId, $name, $color, $scriptContent);
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