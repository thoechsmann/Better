<?
class BetterBase extends IPSModule {

	public function Create() 
    {
		//Never delete this line!
		parent::Create();		
	}
	
	public function ApplyChanges() 
    {
		parent::ApplyChanges();
        $this->RemoveAllButSchedulers();
	}

    protected function SetValueForIdent($ident, $value)
    {
        $id = $this->GetIDForIdent($ident);
        SetValue($id, $value);
    }

    protected function GetValueForIdent($ident)
    { 
        $id = $this->GetIDForIdent($ident);
        return GetValue($id);
    }

    protected function RegisterLink($ident, $name, $targetInstanceID, $position) 
    {
        $link = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);
        if($link !== false)
        {
            IPS_DeleteLink($link);
        }

        $link = IPS_CreateLink();
        IPS_SetName($link, $name);
        IPS_SetIdent($link, $ident);
        IPS_SetParent($link, $this->InstanceID);
        IPS_SetLinkTargetID($link, $targetInstanceID);
        IPS_SetPosition($link, $position);

        return $link;
    }

    /* Type: 
        0 - update
        1 - change
        2 - bigger
        3 - smaller
        4 - value
    */
    protected function RegisterTrigger($ident, $targetId, $script, $triggerType = 1)
    { 
        $id = @IPS_GetObjectIDByIdent($ident, $this->InstanceID); 

        if ($id && IPS_GetEvent($id)['EventType'] <> 0) { 
            IPS_DeleteEvent($id); 
            $id = 0; 
        } 

        if (!$id) { 
            $id = IPS_CreateEvent(0); 
            IPS_SetParent($id, $this->InstanceID); 
            IPS_SetIdent($id, $ident);  
        } 

        if (!IPS_EventExists($id)) throw new Exception("Ident with name $ident is used for wrong object type"); 

        IPS_SetName($id, $ident); 
        IPS_SetHidden($id, true); 
        IPS_SetEventScript($id, "$script;"); 
        IPS_SetEventTrigger($id, $triggerType, $targetId);
        IPS_SetEventActive($id, true); 

        return $id;
    }

    protected function RegisterTimer($ident, $interval, $script) 
    { 
        $id = @IPS_GetObjectIDByIdent($ident, $this->InstanceID); 

        if ($id && IPS_GetEvent($id)['EventType'] <> 1) { 
            IPS_DeleteEvent($id); 
            $id = 0; 
        } 

        if (!$id) { 
            $id = IPS_CreateEvent(1); 
            IPS_SetParent($id, $this->InstanceID); 
            IPS_SetIdent($id, $ident);  
        } 

        if (!IPS_EventExists($id)) throw new Exception("Ident with name $ident is used for wrong object type"); 

        IPS_SetName($id, $ident); 
        IPS_SetHidden($id, true); 
        IPS_SetEventScript($id, "$script;"); 

        if ($interval > 0) { 
            IPS_SetEventCyclic($id, 0, 0, 0, 0, 1, $interval); 
            IPS_SetEventActive($id, true); 
        } else { 
            IPS_SetEventCyclic($id, 0, 0, 0, 0, 1, $interval); 
            IPS_SetEventActive($id, false); 
        } 

        return $id;
    }

    protected function RegisterScheduler($ident, $name = "") 
    { 
        if(empty($name))
            $name = $ident;

        $id = @IPS_GetObjectIDByIdent($ident, $this->InstanceID); 

        if ($id && IPS_GetEvent($id)['EventType'] <> 2) { 
            IPS_DeleteEvent($id); 
            $id = 0; 
        } 

        if (!$id) { 
            $id = IPS_CreateEvent(2); 
            IPS_SetParent($id, $this->InstanceID); 
        }
    
        if (!IPS_EventExists($id)) throw new Exception("Event $ident could not be created."); 

        IPS_SetIdent($id, $ident);  
        IPS_SetName($id, $ident); 

        return $id;
    }

    protected function DeleteObject($ObjectId) { 
        $Object     = IPS_GetObject($ObjectId); 
        $ObjectType = $Object['ObjectType']; 
        switch ($ObjectType) { 
            case 0: // Category 
                DeleteCategory($ObjectId); 
                break; 
            case 1: // Instance 
                EmptyCategory($ObjectId); 
                IPS_DeleteInstance($ObjectId); 
                break; 
            case 2: // Variable 
                IPS_DeleteVariable($ObjectId); 
                break; 
            case 3: // Script 
                IPS_DeleteScript($ObjectId, false); 
                break; 
            case 4: // Event 
                IPS_DeleteEvent($ObjectId); 
                break; 
            case 5: // Media 
                IPS_DeleteMedia($ObjectId, true); 
                break; 
            case 6: // Link 
                IPS_DeleteLink($ObjectId); 
                break; 
            default: 
                Error ("Found unknown ObjectType $ObjectType"); 
        } 
    } 
     
    private function RemoveAllButSchedulers()
    {
        foreach(IPS_GetChildrenIDs($this->InstanceID) as $childId)
        {
            $object = IPS_GetObject($childId);

            if($object["ObjectType"] == 4) // Is event.
            {
                $event = IPS_GetEvent($childId);
                if($event["EventType"] == 2) // Is scheduler.
                    continue;
            }

            $this->DeleteObject($childId);
        }
    }

    private function RemoveAll()
    {
        foreach(IPS_GetChildrenIDs($this->InstanceID) as $childId)
        {
            $this->DeleteObject($childId);
        }
    }

}
?>