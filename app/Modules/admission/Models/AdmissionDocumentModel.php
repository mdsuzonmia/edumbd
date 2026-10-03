<?php
namespace App\Modules\admission\Models;
class AdmissionDocumentModel extends BaseAdmissionModel { protected $table='admission_documents'; protected $allowedFields=['school_id','application_id','document_type','original_name','stored_name','mime_type','file_size','verification_status','verification_note','verified_by','verified_at']; }
