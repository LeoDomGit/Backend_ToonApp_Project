import React, { useEffect, useState } from "react";
import { Dropzone, FileMosaic } from "@dropzone-ui/react";
import axios from "axios";
import Layout from "../../Components/Layout";
import { ToastContainer, toast } from "react-toastify";
import "react-toastify/dist/ReactToastify.css";
import BackgroundGallery from "../../Components/BackgroundGallery";
function Index({ data_images, data_features }) {
    const [files, setFiles] = useState([]);
    const [features, setFeatures] = useState(data_features);
    const [data, setData] = useState(data_images);
    const [feature_id, setFeatureId] = useState(0);
    const updateFiles = (incomingFiles) => {
        setFiles(incomingFiles);
    };
    const handleDelete = async (id) => {
        axios.delete("/backgrounds/" + id).then((res) => {
            if (res.data.check == true) {
                toast.success("Đã xoá thành công !", {
                    position: "top-right",
                });
                setData(res.data.data);
            }
        });
    };
    // ==================================================
    useEffect(() => {
        if (feature_id != 0) {
            axios.get("/backgrounds/" + feature_id).then((res) => {
                setData(res.data);
            });
        }
    }, [feature_id]);
    const handleSubmit = async () => {
        const formData = new FormData();
        formData.append("feature_id", feature_id);
        // Append each file to the FormData
        files.forEach((file) => {
            formData.append("images[]", file.file); // 'images[]' for multiple file inputs in the request
        });

        try {
            const response = await axios.post("/backgrounds", formData, {
                headers: { "Content-Type": "multipart/form-data" },
            });


            if (response.data.check) {
                toast.success("Đã thêm thành công !", {
                    position: "top-right",
                });
                setFiles([]);
                setData(response.data.data); // Clear the files after successful upload
            } else if (response.data.check == false) {
                alert(response.data.msg);
            }
        } catch (error) {
            console.error("Error uploading images:", error);
            toast.error(error, {
                position: "top-right",
            });
        }
    };

      if (response.data.check) {
        toast.success("Đã thêm thành công !", {
            position: "top-right"
          });
        setFiles([]);
        setData(response.data.data); // Clear the files after successful upload
      } else if(response.data.check==false) {
        toast.error(response.data.msg, {
          position: "top-right"
      });
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
                        <div className="row">
                            <div className="col-md mb-3">
                                <label htmlFor="">Feature</label>
                                <select
                                    name=""
                                    defaultValue={feature_id}
                                    className="form-control"
                                    onChange={(e) =>
                                        setFeatureId(e.target.value)
                                    }
                                    id=""
                                >
                                    <option value={0} disabled>
                                        Chọn 1 feature
                                    </option>
                                    {features.map((feature) => {
                                        return (
                                            <option
                                                value={feature.id}
                                                key={feature.id}
                                            >
                                                {feature.name}
                                            </option>
                                        );
                                    })}
                                </select>
                            </div>
                        </div>
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
                                    onDelete={() =>
                                        setFiles(
                                            files.filter(
                                                (f) =>
                                                    f.file.name !==
                                                    file.file.name
                                            )
                                        )
                                    }
                                />
                            ))}
                        </Dropzone>

                        <button
                            onClick={handleSubmit}
                            className="btn btn-primary mt-3"
                        >
                            Upload Images
                        </button>
                    </div>
                </div>
                <BackgroundGallery
                    backgroundImages={data}
                    onDelete={handleDelete}
                />
            </Layout>
        </div>
    );
}

export default Index;
