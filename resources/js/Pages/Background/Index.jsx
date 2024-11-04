import React, { useState } from 'react';
import { Dropzone, FileMosaic } from "@dropzone-ui/react";
import axios from 'axios';
import Layout from "../../Components/Layout";
import { ToastContainer, toast } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';
import BackgroundGallery from "../../Components/BackgroundGallery";
function Index({data_images}) {
    const [files, setFiles] = useState([]);
    const [data,setData]= useState(data_images);
  const updateFiles = (incomingFiles) => {
    setFiles(incomingFiles);
  };
  const handleDelete = async (id) => {
    axios.delete('/backgrounds/'+id).then((res)=>{
        if(res.data.check==true){
            toast.success("Đã xoá thành công !", {
                position: "top-right"
              });
          setData(res.data.data)
        }
      })
  };

  const handleSubmit = async () => {
    const formData = new FormData();
    
    // Append each file to the FormData
    files.forEach((file) => {
      formData.append('images[]', file.file); // 'images[]' for multiple file inputs in the request
    });

    try {
      const response = await axios.post('/backgrounds', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });

      if (response.data.check) {
        toast.success("Đã thêm thành công !", {
            position: "top-right"
          });
        setFiles([]);
        setData(response.data.data); // Clear the files after successful upload
      } else {
        alert(response.data.msg);
      }
    } catch (error) {
      console.error('Error uploading images:', error);
      toast.error(error, {
        position: "top-right"
      });
    }
  };

  return (
    <div>
      <Layout>
      <ToastContainer />
      <div className="row">
            <div className="col-md-4">
            <Dropzone
        onChange={updateFiles}
        value={files}
        accept="image/*"
        maxFiles={10} // Set maximum number of files
      >
        {files.map((file) => (
          <FileMosaic
            key={file.file.name}
            {...file}
            onDelete={() => setFiles(files.filter((f) => f.file.name !== file.file.name))}
          />
        ))}
      </Dropzone>

      <button onClick={handleSubmit} className="btn btn-primary mt-3">
          Upload Images
        </button>
            </div>
      </div>
      <BackgroundGallery backgroundImages={data} onDelete={handleDelete}/>
      </Layout>
    
    </div>
  );
}

export default Index;
