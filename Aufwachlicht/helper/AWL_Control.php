<?php

/**
 * @project       Aufwachlicht/Aufwachlicht
 * @file          AWL_Control.php
 * @author        Ulrich Bittner
 * @copyright     2023 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

declare(strict_types=1);

trait AWL_Control
{
    /**
     * Toggles the wakeup light off or on.
     *
     * @param bool $State
     * false =  off
     * true =   on
     *
     * @param int $Mode
     * 0 =  Manually,
     * 1 =  Weekly Schedule
     *
     * @return bool
     * false =  an error occurred,
     * true =   successful
     *
     * @throws Exception
     */
    public function ToggleWakeUpLight(bool $State, int $Mode = 0): bool
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt.', 0);
        $this->SendDebug(__FUNCTION__, 'Status: ' . json_encode($State), 0);

        //Off
        if (!$State) {
            //Set values
            $this->SetValue('WakeUpLight', false);
            IPS_SetDisabled($this->GetIDForIdent('Color'), false);
            IPS_SetDisabled($this->GetIDForIdent('Brightness'), false);
            IPS_SetDisabled($this->GetIDForIdent('Duration'), false);
            $this->SetValue('ProcessFinished', '');
            @IPS_SetHidden($this->GetIDForIdent('ProcessFinished'), true);
            $this->WriteAttributeInteger('TargetBrightness', 0);
            $this->WriteAttributeInteger('CyclingBrightness', 0);
            $this->WriteAttributeInteger('EndTime', 0);
            $this->SetTimerInterval('IncreaseBrightness', 0);
        }

        //On
        else {
            $lightStatusID = $this->ReadPropertyInteger('LightStatus');
            if ($lightStatusID <= 1 || @!IPS_ObjectExists($lightStatusID)) {
                $this->SendDebug(__FUNCTION__, 'Abbruch, Lichtstatus ist nicht vorhanden!', 0);
                return false;
            }
            $colorID = $this->ReadPropertyInteger('LightColor');
            if ($colorID <= 1 || @!IPS_ObjectExists($colorID)) {
                $this->SendDebug(__FUNCTION__, 'Abbruch, Lichtfarbe ist nicht vorhanden!', 0);
                return false;
            }
            $brightnessID = $this->ReadPropertyInteger('LightBrightness');
            if ($brightnessID <= 1 || @!IPS_ObjectExists($brightnessID)) {
                $this->SendDebug(__FUNCTION__, 'Abbruch, Lichthelligkeit ist nicht vorhanden!', 0);
                return false;
            }

            //Manually
            if ($Mode == 0) {
                $color = $this->GetValue('Color');
                $this->WriteAttributeInteger('TargetBrightness', $this->GetValue('Brightness'));
                $timestamp = time() + $this->GetValue('Duration') * 60;
            }

            //Weekly schedule
            else {
                $day = date('N');

                //Weekday
                if ($day >= 1 && $day <= 5) {
                    $color = $this->ReadPropertyInteger('WeekdayColor');
                    $this->WriteAttributeInteger('TargetBrightness', $this->ReadPropertyInteger('WeekdayBrightness'));
                    $timestamp = time() + $this->ReadPropertyInteger('WeekdayDuration') * 60;
                }

                //Weekend
                else {
                    $color = $this->ReadPropertyInteger('WeekendColor');
                    $this->WriteAttributeInteger('TargetBrightness', $this->ReadPropertyInteger('WeekendBrightness'));
                    $timestamp = time() + $this->ReadPropertyInteger('WeekendDuration') * 60;
                }
            }

            //Set values
            $this->SetValue('WakeUpLight', true);
            IPS_SetDisabled($this->GetIDForIdent('Color'), true);
            IPS_SetDisabled($this->GetIDForIdent('Brightness'), true);
            IPS_SetDisabled($this->GetIDForIdent('Duration'), true);
            $this->SetValue('ProcessFinished', date('d.m.Y, H:i:s', $timestamp));
            @IPS_SetHidden($this->GetIDForIdent('ProcessFinished'), false);

            //Set attributes
            $this->WriteAttributeInteger('CyclingBrightness', 1);
            $this->WriteAttributeInteger('EndTime', $timestamp);

            //Set light values
            @RequestAction($brightnessID, 1);
            @RequestAction($colorID, $color);
            @RequestAction($lightStatusID, true);

            //Set next cycle
            $this->SetTimerInterval('IncreaseBrightness', $this->CalculateNextCycle() * 1000);

            return true;
        }
    }

    /**
     * Increases the brightness of the light.
     *
     * @return void
     * @throws Exception
     */
    public function IncreaseBrightness(): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt.', 0);

        $lightStatusID = $this->ReadPropertyInteger('LightStatus');
        if ($lightStatusID <= 1 || @!IPS_ObjectExists($lightStatusID)) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, Lichtstatus ist nicht vorhanden!', 0);
            $this->ToggleWakeUpLight(false);
            return;
        }

        $lightBrightnessID = $this->ReadPropertyInteger('LightBrightness');
        if ($lightBrightnessID <= 1 || @!IPS_ObjectExists($lightBrightnessID)) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, Lichthelligkeit ist nicht vorhanden!', 0);
            $this->ToggleWakeUpLight(false);
            return;
        }

        //Abort, the light has been switched off again and the user no longer wishes to increase the brightness
        if (!GetValue($lightStatusID) || GetValue($lightBrightnessID) == 0) {
            $this->ToggleWakeUpLight(false);
            return;
        }

        $actualBrightness = GetValue($lightBrightnessID);

        //Abort, if actual brightness is higher or equal than the next cycling brightness
        if ($actualBrightness >= ($this->ReadAttributeInteger('CyclingBrightness') + 1)) {
            $this->ToggleWakeUpLight(false);
            return;
        }

        //Increase the brightness
        if ($actualBrightness >= 1 && $actualBrightness < $this->ReadAttributeInteger('TargetBrightness')) {
            @RequestAction($lightBrightnessID, $actualBrightness + 1);
            $this->WriteAttributeInteger('CyclingBrightness', $actualBrightness + 1);
        }

        //Check last cycle
        if ($this->ReadAttributeInteger('CyclingBrightness') == $this->ReadAttributeInteger('TargetBrightness')) {
            $this->ToggleWakeUpLight(false);
            return;
        }

        //Set next cycle
        $this->SetTimerInterval('IncreaseBrightness', $this->CalculateNextCycle() * 1000);
    }

    #################### Private

    /**
     * Calculates the next cycle.
     *
     * @return int
     * @throws Exception
     */
    private function CalculateNextCycle(): int
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt.', 0);
        $lightBrightnessID = $this->ReadPropertyInteger('LightBrightness');
        if ($lightBrightnessID <= 1 || @!IPS_ObjectExists($lightBrightnessID)) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, Lichthelligkeit ist nicht vorhanden!', 0);
            return 0;
        }
        $lightBrightness = GetValue($lightBrightnessID);
        if ($lightBrightness == 0 || $lightBrightness >= $this->ReadAttributeInteger('TargetBrightness')) {
            $this->ToggleWakeUpLight(false);
            return 0;
        }
        $dividend = $this->ReadAttributeInteger('EndTime') - time();
        $divisor = $this->ReadAttributeInteger('TargetBrightness') - $lightBrightness;
        //Check dividend and divisor
        if ($dividend <= 0 || $divisor <= 0) {
            $this->ToggleWakeUpLight(false);
            return 0;
        }
        $remainingTime = intval(round($dividend / $divisor));
        $this->SendDebug(__FUNCTION__, 'Nächste Ausführung in: ' . $remainingTime, 0);
        return $remainingTime;
    }
}