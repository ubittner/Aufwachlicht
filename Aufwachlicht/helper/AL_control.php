<?php

/** @noinspection PhpUndefinedFunctionInspection */
/** @noinspection PhpUnused */

/*
 * @module      Aufwachlicht
 *
 * @prefix      AL
 *
 * @file        AL_control.php
 *
 * @developer   Ulrich Bittner
 * @copyright   (c) 2020
 * @license    	CC BY-NC-SA 4.0
 *              https://creativecommons.org/licenses/by-nc-sa/4.0/
 *
 * @see         https://github.com/ubittner/Aufwachlicht
 *
 */

declare(strict_types=1);

trait AL_control
{
    /**
     * Toggles the wakeup light off or on.
     *
     * @param bool $State
     * false    = off
     * true     = on
     */
    public function ToggleWakeupLight(bool $State): void
    {
        if (!$this->CheckUsability()) {
            return;
        }
        $id = $this->ReadPropertyInteger('Light');
        if ($id != 0 && @IPS_ObjectExists($id)) {
            $lightState = @PHUE_GetState($id);
            //Off
            if (!$State) {
                $this->SetValue('WakeUpLight', false);
                $this->ResetParameters();
                if ($lightState) {
                    @PHUE_SwitchMode($id, false);
                }
            }
            //On
            if ($State) {
                $this->SetValue('WakeUpLight', true);
                if ($lightState) {
                    return;
                }
                @PHUE_DimSet($id, 1);
                $color = $this->ReadPropertyInteger('WakeUpColor');
                $hex = dechex($color);
                $hexColor = '#' . strtoupper($hex);
                @PHUE_ColorSet($id, $hexColor);
                $this->WriteAttributeInteger('CyclingBrightness', 1);
                $milliseconds = intval(floor(($this->ReadPropertyInteger('WakeUpDuration') * 60 * 1000) / ($this->ReadPropertyInteger('WakeUpBrightness') - 1)));
                $this->WriteAttributeInteger('CyclingInterval', $milliseconds);
                $this->SetTimerInterval('WakeUpMode', $milliseconds);
            }
        }
    }

    /**
     * Executes the wakeup mode (dimming), used by timer.
     */
    public function ExecuteWakeUpMode(): void
    {
        if (!$this->CheckUsability()) {
            return;
        }
        $id = $this->ReadPropertyInteger('Light');
        if ($id != 0 && @IPS_ObjectExists($id)) {
            //Abort, if light was already switched off, user don't want to wait till cycling end
            if (@!PHUE_GetState($id)) {
                $this->SetValue('WakeUpLight', false);
                $this->ResetParameters();
                return;
            }
            //Abort, if or actual brightness is higher then the cycling brightness
            $cyclingBrightness = $this->ReadAttributeInteger('CyclingBrightness');
            if ($this->GetLightBrightness() > $cyclingBrightness) {
                $this->SetValue('WakeUpLight', true);
                $this->ResetParameters();
                return;
            }
            //Dimming up
            if ($cyclingBrightness < $this->ReadPropertyInteger('WakeUpBrightness')) {
                @PHUE_DimSet($id, $cyclingBrightness + 1);
                $milliseconds = $this->ReadAttributeInteger('CyclingInterval');
                $this->SetTimerInterval('WakeUpMode', $milliseconds);
                $this->WriteAttributeInteger('CyclingBrightness', $cyclingBrightness + 1);
            } else {
                $this->ResetParameters();
            }
        }
    }

    /**
     * Updates the light state, used by timer.
     */
    public function UpdateLightState(): void
    {
        if (!$this->CheckUsability()) {
            return;
        }
        $updateInterval = $this->ReadPropertyInteger('UpdateInterval') * 1000;
        if ($updateInterval > 0) {
            $id = $this->ReadPropertyInteger('Light');
            if ($id != 0 && @IPS_ObjectExists($id)) {
                if (@!PHUE_GetState($id)) {
                    $this->SetValue('WakeUpLight', false);
                    $this->ResetParameters();
                } else {
                    $this->SetValue('WakeUpLight', true);
                }
            }
        }
        $this->SetTimerInterval('UpdateLightState', $updateInterval);
    }

    #################### Private

    private function GetLightBrightness(): int
    {
        $brightness = 0;
        $id = $this->ReadPropertyInteger('Light');
        if ($id != 0 && @IPS_ObjectExists($id)) {
            $children = IPS_GetChildrenIDs($id);
            if (!empty($children)) {
                $children = IPS_GetChildrenIDs($id);
                if (!empty($children)) {
                    foreach ($children as $child) {
                        $ident = @IPS_GetObject($child)['ObjectIdent'];
                        if ($ident == 'HUE_Brightness') {
                            $brightness = GetValueInteger($child);
                        }
                    }
                }
            }
        }
        return $brightness;
    }

    private function ResetParameters(): void
    {
        $this->WriteAttributeInteger('CyclingBrightness', 0);
        $this->WriteAttributeInteger('CyclingInterval', 0);
        $this->SetTimerInterval('WakeUpMode', 0);
    }
}