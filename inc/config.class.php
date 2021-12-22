<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.
 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------
 LICENSE
 This file is part of GLPI.
 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.
 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.
 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

class PluginRemotesupportConfig extends CommonDBTM {

   static protected $notable = true;

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (!$withtemplate) {
         if ($item->getType() == 'Config') {
            return __('Remote Support plugin');
         }
      }
      return '';
   }

   static function configUpdate($input) {
      $input['configuration'] = 1 - $input['configuration'];
      return $input;
   }

   function showFormRemotesupport() {
      global $CFG_GLPI;

      if (!Session::haveRight("config", UPDATE)) {
         return false;
      }

      $my_config = Config::getConfigurationValues('plugin:Remotesupport');

      echo "<form name='form' action=\"".Toolbox::getItemTypeFormURL('Config')."\" method='post'>";
      echo "<div class='center' id='tabsbody'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<input type='hidden' name='config_class' value='".__CLASS__."'>";
      echo "<input type='hidden' name='config_context' value='plugin:Remotesupport'>";
      echo "<tr><th colspan='4'>" . __('Remote support setup') . "</th></tr>";
      echo "<tr>";	
      echo "<td >" . __('Run Mode:') . "</td>";
      echo "<td>";
      Dropdown::showFromArray('run_mode', array('None' => 'None','Serial'=>'Serial','Parallel' => 'Parallel'), array('value' => $my_config['run_mode']));
      echo "</td>";
      echo "<td >" . __('EasyNoVNC Installed:') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("easy_novnc", $my_config['easy_novnc']);
      echo "</td></tr>";
      echo "<tr>";
      echo "<td >" . __('Show in Computers:') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("show_in_computers", $my_config['show_in_computers']);
      echo "</td>";
      echo "<td >" . __('Show in Tickets:') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("show_in_tickets", $my_config['show_in_tickets']);
      echo "</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<td >" . __('Threads:') . "</td>";
      echo "<td>";
      Dropdown::showNumber("threads",
                           [
                            'value' => 100,
                            'min' => 1,
                            'max' => 100
                           ]);
      echo "</td>";
      echo "<td >" . __('Fusion inventory:') . "</td>";
      echo "<td>";
      Dropdown::showYesNo("fusion", $my_config['fusion']);
      echo "</td>";
 
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td colspan='4' class='center'>";
      echo "<input type='submit' name='update' class='submit' value=\""._sx('button', 'Save')."\">";
      echo "</td></tr>";

      echo "</table></div>";
      Html::closeForm();
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item->getType() == 'Config') {
         $config = new self();
         $config->showFormRemotesupport();
      }
   }

}
