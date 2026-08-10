<p align="center">

![Monica’s Logo](https://user-images.githubusercontent.com/61099/242266547-63d98bd9-35f3-4dfe-92f4-a4a8dd75aa5c.png)

</p>

<h1 align="center">Document your life</h1>


<h3 align="center">Problem Analysis</h3>

●​. How the existing import flow works <br/>
  api/import - route - endpoint 
  Domains/Contact/ManageContact/Api/Controllers/ContactController.php
  import() method.
  upload file , validate and process valid data from file for contacts table and invalid data for error table.

  api/import/{id} - route - endpoint 
  Domains/Contact/ManageContact/Api/Controllers/ContactController.php
  show() method.
  checking progress for single recorde of uploaded file

  import/contacts/cancel - route - endpoint
  Domains/Contact/ManageContact/Api/Controllers/ContactController.php
  cancel() method.
  for canceling uploading file.


●​ Which existing components you reused or modified
  i'm reused these 
  use App\Domains\Contact\ManageContact\Services\CreateContact;
  service to save contacts .
  Following same to same monica crm coding pattern and conventions.

●​ Any important assumptions you made
  yes. I have written a controller er Domains/Contact/ManageContact/Api/Controllers/ContactController.php
  and validate uploading file and pass validated data to service with this controller.
  following monica crm coding pattern and conventions.
  creating ImportContacts service validating rules , essential methods and pass to the 
  ProcessContactImportJob job.
  ProcessContactImportJob process file and save validate data to contacts table and in-valid data to import_errors table.



<h3 align="center">Technical Questions</h3>

*How would you allow a user to cancel a running import?
Here, i'm using and endpoint "api/import/contacts/cancel" to updating file status "processing" to "cancelling",
then have written a coding previously inside ProcessContactImportJob to check if 
status is "cancelling" or not if every 20 rows of import?
if cancle endpoint change status to cancelling then condition will be true and exit the process.

*What metrics would you monitor for this import system?
  these "api/import/{id}" returning use the current states of data.


<h3 align="center">Technical Review</h3>

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
  ***********

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
  *********

  ***********
  ProcessContactImportJob
  ************
    now , datas are inside ProcessContactImportJob 
    here
    1. $timeout for 1800 seconds queue will try.
    2. if job processing fails then $tries untill 3 times 
    3. relif for 10 seconds after tring each time.
    4. dependency injecton for ImportJobs model 
    5. then we are using pre writen monica crm's service to save each contacts in uploaded file CreateContact    
    6. now checking if uploading file is previously uploaded or not.
    7. if exits then throw error "file exists" else upload 
    8. set status processing, upload the file to non-public location and read uploaded file from inside each row and save to contacts, if any error happend, then skip the row for contacts table 
    and throw to catch block, catch block the row and save to import_errors table.
    9. before read contacts , inside loop , here is a checking , if status is cancelling of not
    if status is cancelling found then exit the process , that's how we achieve cancling uploading 
    files functionality.
    10. if any error happening in uploading file, or duplicate then it will throw to it's outer catch block then catch block will catch the file uploading issue and show the error without interupped 
    process.
  *************









