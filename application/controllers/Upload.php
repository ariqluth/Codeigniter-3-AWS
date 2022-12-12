<?php
require 'vendor/autoload.php';
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
defined('BASEPATH') or exit('No direct script access allowed');

class Upload extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Upload_model');
    }
    
    public function index()
    {
        $data['uploads'] = $this->Upload_model->get_all();
        $this->load->view('upload/upload_list', $data);
    }

    public function add()
    {
        $this->load->view('upload/upload_create');
    }
    
    public function create()
    {
        $data = array(
            'author' => $this->input->post('author'),
        );
        
        if (!empty($_FILES['image']['name'])) {
            $image = $this->_do_upload();
            $data['image'] = $image;
        }
        
        $this->Upload_model->insert($data);
        redirect('', $data);
    }

    public function edit($id)
    {
        $data['upload'] = $this->Upload_model->get_by_id($id);
        $this->load->view('upload/upload_update', $data);
    }

    public function update()
    {
        $id = $this->input->post('id');
        $author = $this->input->post('author');

        $data = array(
            'author' => $author,
        );

        if (!empty($_FILES['image']['name'])) {
            $image = $this->_do_upload();

            $upload = $this->Upload_model->get_by_id($id);
            if (file_exists('assets/upload/images/'.$upload->image) && $upload->image) {
                unlink('assets/upload/images/'.$upload->image);
            }

            $data['image'] = $image;
        }

        $this->Upload_model->update($data, $id);
        redirect('');
    }

    private function _do_upload()
    {
        $image_name = time().'-'.$_FILES["image"]['name'];

        $config['upload_path'] 		= 'assets/upload/images/';
        $config['allowed_types'] 	= 'gif|jpg|png';
        $config['max_size'] 		= 200;
        $config['max_widht'] 		= 2000;
        $config['max_height']  		= 2000;
        $config['file_name'] 		= $image_name;

        //  // Instantiate an Amazon S3 client.
        //  $s3_Config = array (
        //     'key'    => 'AKIAU65DXABT67RJ2FE5',
        //      'secret' => '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'
        //  );

        //  $s3Client = new S3Client([
        //     'version' => 'latest',
        //     'region'  => 'ap-southeast-1',
        //     'credentials' => [
        //         'key'    => $s3_Config['key'],
        //         'secret' => $s3_Config['secret'],
        //     ]
        // ]);

        // $bucket = 'server-bucket2';
        // $file_Path = "/assets/upload/images/".$image_name;
        // $key = basename($file_Path);

        // try {
        //     $result = $s3Client->putObject([
        //         'Bucket' => $bucket,
        //         'Key'    => $key,
        //         // 'SourceFilePath' => fopen($file_Path, 'r'),
        //         'Body'   => fopen($file_Path, 'r') or die ('Cannot open file : '.$file_Path),
        //         'ACL'    => 'public-read', // make file 'public'
        //     ]);
        //   $msg = 'File has been uploaded';
        // } catch (Aws\S3\Exception\S3Exception $e) {
        //     // $msg = 'File has been uploaded';
        //     echo $e->getMessage();
        // }

        // disiap kann s3 

        // / AWS Info
        $bucketName = 'server-bucker2';
        $IAM_KEY = 'AKIAU65DXABT6RPJO7VO';
        $IAM_SECRET = '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';
    
        // Connect to AWS
        try {
            // You may need to change the region. It will say in the URL when the bucket is open
            // and on creation. us-east-2 is Ohio, us-east-1 is North Virgina
            echo 1;
            $s3 = S3Client::factory(
                array(
                    'credentials' => array(
                        'key' => $IAM_KEY,
                        'secret' => $IAM_SECRET
                    ),
                    'version' => 'latest',
                    'region'  => 'ap-southeast-1'
                )
            );
            
        } catch (Exception $e) {
            
        die("Error: " . $e->getMessage());
        }
    
        // For this, I would generate a unqiue random string for the key name. But you can do whatever.
        $keyName = 'test_example/' . basename($_FILES["image"]['tmp_name']);   //ftp is file name at index.php
        $pathInS3 = 'https://s3.ap-southeast-1.amazonaws.com/' . $bucketName . '/' . $keyName;
    
        // Add it to S3
        try {
            
            if (!file_exists('assets/upload/images/'.$image_name)) {
                echo 3;
                mkdir('assets/upload/images/'.$image_name);
            }
                
            $tempFilePath = 'assets/upload/images/'.$image_name. basename($_FILES["image"]['name']);
         
            $tempFile = fopen($tempFilePath, "w") or die("Error: Unable to open file.");
            
            $fileContents = file_get_contents($_FILES["image"]['tmp_name']);
            $tempFile = file_put_contents($tempFilePath, $fileContents);
    
            $s3->putObject(
                array(
                    'Bucket'=>$bucketName,
                    'Key' =>  $keyName,
                    'SourceFile' => $tempFilePath,
                    'StorageClass' => 'REDUCED_REDUNDANCY',
                    'ACL'   => 'public-read'
                )
            );
    
        } catch (S3Exception $e) {
            die('Error:' . $e->getMessage());
        } catch (Exception $e) {
            die('Error:' . $e->getMessage());
        }
    
    
        echo 'Done';
    

        $this->load->library('upload', $config);
       

        if (!$this->upload->do_upload('image')) {
            $this->session->set_flashdata('msg', $this->upload->display_errors('', ''));
            redirect('');
        }
        return $this->upload->data('file_name');
        // $msg = 'File has been uploaded';
    }

    public function detail($id)
    {
        $data['upload'] = $this->Upload_model->get_by_id($id);
        $this->load->view('upload/upload_detail', $data);
    }
        
    public function delete($id)
    {
        $upload = $this->Upload_model->get_by_id($id);
        if (file_exists('assets/upload/images/'.$upload->image) && $upload->image) {
            unlink('assets/upload/images/'.$upload->image);
        }
        $this->Upload_model->delete($id);
        redirect('');
    }
   

}

/* End of file Upload.php */
