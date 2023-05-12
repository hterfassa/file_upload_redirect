<?php

namespace Drupal\file_upload_redirect\Form; //Defines the namespace for the class.

use Drupal\Core\Form\FormBase; //Imports the FormBase class from the Drupal\Core\Form namespace
use Drupal\Core\Form\FormStateInterface; //Imports the FormStateInterface class from the Drupal\Core\Form namespace.
use Drupal\Core\Url; //Imports the Url class from the Drupal\Core namespace.
use Drupal\file\Entity\File; //Imports the File class from the Drupal\file\Entity namespace.
use Drupal\node\Entity\Node;

class FileUploadForm extends FormBase { //Declares the FileUploadForm class and extends the FormBase class.

  public function getFormId() { //Declares the getFormId() method which returns a unique ID for the form.
    return 'file_upload_redirect.upload_form'; //Returns a string as the ID for the form.
  }

  //Declares the buildForm() method which builds the form.
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Add a heading to the form.
    $form['description'] = [
      '#markup' => '<h2>Use this page to upload a PDF file.</h2>',
    ];
  
    // Add a field for the user's first name.
    $form['first_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First Name'),
      '#required' => true,
    ];
  
    // Add a field for the user's last name.
    $form['last_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Last Name'),
      '#required' => true,
    ];
  
    // Add a field for the user's email address.
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#required' => true,
    ];
  
    // Add a managed file field to the form for uploading a PDF file.
    $form['file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Upload a PDF File'),
      '#upload_location' => 'public://uploads',
      // Set upload validators to limit the file types and size that can be uploaded.
      '#upload_validators' => [
        'file_validate_extensions' => ['pdf', 'doc', 'docx'],
        'file_validate_size' => [25600000],
      ],
    ];
  
    // Add a submit button to the form.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Upload'),
    ];
  
    return $form;
  }    

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $validators = $form['file']['#upload_validators'];
    $file = $form_state->getValue('file');
  
    // Check if the file field is empty.
    if (empty($file)) {
      $form_state->setErrorByName('file', $this->t('The selected file could not be uploaded.'));
    }
  
    // Check if the first name field is empty.
    if (empty(trim($form_state->getValue('first_name')))) {
      $form_state->setErrorByName('first_name', $this->t('The first name field is required.'));
    }
  
    // Check if the last name field is empty.
    if (empty(trim($form_state->getValue('last_name')))) {
      $form_state->setErrorByName('last_name', $this->t('The last name field is required.'));
    }
  
    // Check if the email field is empty or invalid.
    $email = trim($form_state->getValue('email'));
    if (empty($email)) {
      $form_state->setErrorByName('email', $this->t('The email field is required.'));
    }
    elseif (!\Drupal::service('email.validator')->isValid($email)) {
      $form_state->setErrorByName('email', $this->t('The email address you entered is not valid.'));
    }
  }  
  

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_file = $form_state->getValue('file', 0);
    
    // If a file was uploaded, create a new file entity and save it.
    if (isset($form_file[0]) && !empty($form_file[0])) {
      $file = File::load($form_file[0]);
      $file->setPermanent();
      $file->save();
    }
    
    // Get the values of the first name, last name, and email fields.
    $first_name = trim($form_state->getValue('first_name'));
    $last_name = trim($form_state->getValue('last_name'));
    $email = trim($form_state->getValue('email'));
  
    // Create a new node entity of the "resume" content type.
    $node = Node::create([
      'type' => 'resume',
      'title' => $first_name . ' ' . $last_name,
      'field_resume_email' => $email,
      'field_resume_file' => [
        'target_id' => $file->id(),
        'alt' => $file->getFilename(),
        'title' => $file->getFilename(),
      ],
    ]);
  
    // Save the node entity.
    $node->save();
    
    // Set a message for the user.
    $this->messenger()->addMessage($this->t('Your file was uploaded successfully, %name!', ['%name' => $first_name]));
    
    // Send an email notification to the site administrator.
    $mailManager = \Drupal::service('plugin.manager.mail');
    $module = 'file_upload_redirect';
    $key = 'file_upload_notification';
    $to = \Drupal::config('system.site')->get('mail');
    $params = [
      'first_name' => $first_name,
      'last_name' => $last_name,
      'email' => $email,
    ];
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $send = true;
    $result = $mailManager->mail($module, $key, $to, $langcode, $params, null, $send);
    
    return;
  }  
}