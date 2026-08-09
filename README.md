<p align="center">

![Monica’s Logo](https://user-images.githubusercontent.com/61099/242266547-63d98bd9-35f3-4dfe-92f4-a4a8dd75aa5c.png)

</p>
<h1 align="center">Document your life</h1>

Import work flow working like this , 
Coding convention is same to same like monica crm existing codes.

1. api/import - route - endpoint 
  controller - Domains/Contact/ManageContact/Api/Controllers/ContactController.php

  *********
  import method 
  ***********
  1.validating valid vault_id and contacts.csv file.
  2.checking authorization for vault_editor.
  3.validate contacts.csv file.
  4.arranging a associative array for $data and passing to ImportContacts Service and calling execute method

  *********
  ImportContacts Service
  *********
  inside execute method 
  1.validating data 
  2.storing file in non public location
  3.store file in non public location
  4.create import job record
  5.dispatching QueueJob
  6.saving data in import_jobs table with  ProcessContactImportJob

  ***********
  ProcessContactImportJob
  ************


  







●​ Which existing components you reused or modified
●​ Any important assumptions you made