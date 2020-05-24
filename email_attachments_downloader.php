<?php
function att_download($hostname,$username,$password){

    
    GLOBAL $path;
    /* connect to email */
    $inbox = imap_open($hostname,$username,$password) or die('Cannot connect to Gmail: ' . imap_last_error());
    
    
    /* Set ALL to download all emails,
    * set NEW to download only new emails.
    */
    $emails = imap_search($inbox,'ALL');
    
    /* if the above search is set to 'ALL' */
    $max_emails = 16;
    
    
    /* iterate through each email founded */
    if($emails) {
    
        $count = 1;
    
        /* sort emails(newest on top) */
        rsort($emails);
    
        foreach($emails as $email_number) 
        {
    
            $overview = imap_fetch_overview($inbox,$email_number,0);
    
            $message = imap_fetchbody($inbox,$email_number,2);
    
            /* get mail structure */
            $structure = imap_fetchstructure($inbox, $email_number);
    
            $attachments = array();
    
            if(isset($structure->parts) && count($structure->parts)) 
            {
                for($i = 0; $i < count($structure->parts); $i++) 
                {
                    $attachments[$i] = array(
                        'is_attachment' => false,
                        'filename' => '',
                        'name' => '',
                        'attachment' => ''
                    );
    
                    if($structure->parts[$i]->ifdparameters) 
                    {
                        foreach($structure->parts[$i]->dparameters as $object) 
                        {
                            if(strtolower($object->attribute) == 'filename') 
                            {
                                $attachments[$i]['is_attachment'] = true;
                                $attachments[$i]['filename'] = $object->value;
                            }
                        }
                    }
    
                    if($structure->parts[$i]->ifparameters) 
                    {
                        foreach($structure->parts[$i]->parameters as $object) 
                        {
                            if(strtolower($object->attribute) == 'name') 
                            {
                                $attachments[$i]['is_attachment'] = true;
                                $attachments[$i]['name'] = $object->value;
                            }
                        }
                    }
    
                    if($attachments[$i]['is_attachment']) 
                    {
                        $attachments[$i]['attachment'] = imap_fetchbody($inbox, $email_number, $i+1);
    
                        if($structure->parts[$i]->encoding == 3) 
                        { 
                            $attachments[$i]['attachment'] = base64_decode($attachments[$i]['attachment']);
                        }
                        
                        elseif($structure->parts[$i]->encoding == 4) 
                        { 
                            $attachments[$i]['attachment'] = quoted_printable_decode($attachments[$i]['attachment']);
                        }
                    }
                }
            }
    
            foreach($attachments as $attachment)
            {
                if($attachment['is_attachment'] == 1)
                {
                    $filename = $attachment['name'];
                    if(empty($filename)) $filename = $attachment['filename'];
    
                    if(empty($filename)) $filename = time() . ".dat";

                    $filename = mb_decode_mimeheader($attachment['name']);
                    $filename = strtolower($filename);
                    
                    /* set the file format */
                    if( pathinfo($filename, PATHINFO_EXTENSION) != "pdf" ){
                        echo "\n FATAL ERROR -> ".$filename." DOESN'T HAVE REQUIRED EXTENSION (PDF) -> SKIPPING FILE";
                        continue;
                    }
    
                    $fp = fopen($path . "/" . $email_number . "-" . $filename, "w+");
                    fwrite($fp, $attachment['attachment']);
                    fclose($fp);
                }
    
            }
    
            if($count++ >= $max_emails) break;
        }
    
    } 
    
    /* close the connection */
    imap_close($inbox);
    
    echo "Done";
} 


$hostname = 'host EXAMPLE:{outlook.office365.com:993/imap/ssl}';
$username = 'email'; 
$password = 'pass';

$path = $argv[1];

@mkdir($path, 0777, true);

att_download($hostname,$username,$password);

?>