<?php if(!defined('APPLICATION')) exit();
/* 	Copyright 2013-2014 Zachary Doll
 * 	This program is free software: you can redistribute it and/or modify
 * 	it under the terms of the GNU General Public License as published by
 * 	the Free Software Foundation, either version 3 of the License, or
 * 	(at your option) any later version.
 *
 * 	This program is distributed in the hope that it will be useful,
 * 	but WITHOUT ANY WARRANTY; without even the implied warranty of
 * 	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * 	GNU General Public License for more details.
 *
 * 	You should have received a copy of the GNU General Public License
 * 	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
$PluginInfo['RandomImages'] = array(
    'Name' => 'Random Images',
    'Description' => 'Renders a list of random images from the current discussion model.',
    'Version' => '0.6',
    'MobileFriendly' => TRUE,
    'RequiredApplications' => array('Vanilla' => '2.0.18.8'),
    'SettingsUrl' => '/settings/randomimages',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'Author' => 'Zachary Doll',
    'AuthorEmail' => 'hgtonight@daklutz.com',
    'AuthorUrl' => 'http://www.daklutz.com/',
);

class RandomImagesPlugin extends Gdn_Plugin {

  public function DiscussionsController_BeforeRenderAsset_Handler($Sender) {
    if($Sender->EventArguments['AssetName'] == 'Content') {
      $Discussions = $Sender->Data['Discussions'];
      $this->_RenderImageList($Discussions);
    }
  }

  public function CategoriesController_BeforeRenderAsset_Handler($Sender) {
    if($Sender->EventArguments['AssetName'] == 'Content') {
      $Discussions = $Sender->Data['Discussions'];
      $this->_RenderImageList($Discussions);
    }
  }

  public function DiscussionsController_Render_Before($Sender) {
    $this->_AddResources($Sender);
  }

  public function CategoriesController_Render_Before($Sender) {
    $this->_AddResources($Sender);
  }

  private function _RenderImageList($DiscussionModel) {
    if(method_exists($DiscussionModel, 'Result')) {
      $Discussions = $DiscussionModel->Result();
    }
    else {
      $Discussions = $DiscussionModel;
    }
    $Class = C('Plugins.RandomImages.Float', 'Right');
    $Images = array();
    $ImageMax = C('Plugins.RandomImages.MaxLength', 10);
    foreach($Discussions as $Discussion) {
      $this->_ExtractImages($Discussion, $Images);
    }
    // remove random images until we are under the max length
    while(count($Images) > $ImageMax) {
      unset($Images[array_rand($Images)]);
    }
    // shuffle it
    $ImageList = '';
    if(shuffle($Images)) {
      foreach($Images as $Image) {
        // assemble a list
        $ImageList .= Wrap(Anchor(Img($Image['image'], array('class' => 'RandomImage')), $Image['url']), 'li');
      }
    }
    echo Wrap($ImageList, 'ul', array('id' => 'RandomImageList', 'class' => $Class));
  }

  protected function _ExtractImages($TDiscussion, &$Images) {
    $Discussion = (array) $TDiscussion;
    $ImageSrcs = NULL;
    $ImageFound = preg_match_all('/([a-z\-_0-9\/\:\.]*\.(jpg|jpeg|png|gif))/i', $Discussion['Body'], $ImageSrcs);
    if($ImageFound) {
      $i = 0;
      while($i < $ImageFound) {
        array_push($Images, array('image' => $ImageSrcs[0][$i], 'url' => $Discussion['Url']));
        $i++;
      }
    }
  }

  public function SettingsController_RandomImages_Create($Sender) {
    $Sender->Permission('Garden.Settings.Manage');

    $Validation = new Gdn_Validation();
    $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
    $ConfigurationModel->SetField(array(
        'Plugins.RandomImages.MaxLength',
        'Plugins.RandomImages.Float',
        'Plugins.RandomImages.DisableCSS'
    ));
    $Sender->Form->SetModel($ConfigurationModel);

    if($Sender->Form->AuthenticatedPostBack() === FALSE) {
      $Sender->Form->SetData($ConfigurationModel->Data);
    }
    else {
      $ConfigurationModel->Validation->ApplyRule('Plugins.RandomImages.MaxLength', 'Required');
      $ConfigurationModel->Validation->ApplyRule('Plugins.RandomImages.MaxLength', 'Integer');
      $ConfigurationModel->Validation->ApplyRule('Plugins.RandomImages.Float', 'Required');
      $ConfigurationModel->Validation->ApplyRule('Plugins.RandomImages.Float', 'String');
      if($Sender->Form->Save() !== FALSE) {
        $Sender->InformMessage('<span class="InformSprite Sliders"></span>' . T("Your changes have been saved."), 'HasSprite');
      }
    }

    $Sender->AddSideMenu();
    $Sender->Render($this->GetView('settings.php'));
  }

  public function Base_GetAppSettingsMenuItems_Handler($Sender) {
    $Menu = &$Sender->EventArguments['SideMenu'];
    $Menu->AddLink('Add-ons', 'Random Images', 'settings/randomimages', 'Garden.Settings.Manage');
  }

  private function _AddResources($Sender) {
    if(!C('Plugins.RandomImages.DisableCSS', FALSE)) {
      $Sender->AddCSSFile($this->GetResource('design/randomimages.css', FALSE, FALSE));
    }
  }

}
