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
    const [groupBackgrounds, setGroupBackgrounds] = useState([]); // Store selected groups
    const [newGroup, setNewGroup] = useState("");
    const [allGroups, setAllGroups] = useState([]); // List of available groups

    const updateFiles = (incomingFiles) => {
        setFiles(incomingFiles);
    };

    const handleDelete = async (id) => {
        try {
            const res = await axios.delete("/backgrounds/" + id);
            if (res.data.check) {
                toast.success("Image deleted successfully!", {
                    position: "top-right",
                });
                setData(res.data.data);
            }
        } catch (error) {
            toast.error("Error deleting image!", {
                position: "top-right",
            });
        }
    };

    useEffect(() => {
        if (feature_id !== 0 && groupBackgrounds.length > 0) {
            axios
                .get(
                    `/backgrounds/${feature_id}?group=${groupBackgrounds.join(
                        ","
                    )}`
                )
                .then((res) => {
                    setData(res.data);
                })
                .catch((error) => {
                    console.error("Error fetching background images:", error);
                });
        }
    }, [feature_id, groupBackgrounds]); // Fetch data when feature or group changes

    useEffect(() => {
        if (feature_id !== 0) {
            axios.get(`/groups/${feature_id}`).then((res) => {
                if (res.data) {
                    setAllGroups(res.data);
                }
            });
        }
    }, [feature_id]); // Fetch groups only when feature_id changes

    const handleAddGroup = async () => {
        if (newGroup.trim() !== "") {
            if (!groupBackgrounds.includes(newGroup.trim())) {
                setGroupBackgrounds((prevGroups) => [
                    ...prevGroups,
                    newGroup.trim(),
                ]);

                try {
                    const res = await axios.post("/save-group-backgrounds", {
                        group_backgrounds: [
                            ...groupBackgrounds,
                            newGroup.trim(),
                        ],
                        feature_id: feature_id,
                    });

                    if (res.data.status) {
                        toast.success("Group added successfully!", {
                            position: "top-right",
                        });
                        setNewGroup(""); // Reset input field after success
                    } else {
                        toast.error("Error adding group.", {
                            position: "top-right",
                        });
                    }
                } catch (error) {
                    console.error("Error adding group:", error);
                    toast.error("Error adding group.", {
                        position: "top-right",
                    });
                }
            } else {
                toast.warning("This group already exists!", {
                    position: "top-right",
                });
            }
        }
    };

    const handleRemoveGroup = async (groupName) => {
        try {
            const res = await axios.post("/remove-group", {
                group_name: groupName,
                feature_id: feature_id,
            });
            if (res.data.status) {
                toast.success("Group removed successfully!", {
                    position: "top-right",
                });

                setGroupBackgrounds(
                    groupBackgrounds.filter((group) => group !== groupName)
                );

                const updatedGroups = await axios.get(`/groups/${feature_id}`);
                if (updatedGroups.data) {
                    setAllGroups(updatedGroups.data);
                }
            }
        } catch (error) {
            toast.error("Error removing group!", {
                position: "top-right",
            });
        }
    };

    const handleSubmit = async () => {
        if (feature_id === 0 || files.length === 0) {
            toast.warning(
                "Please select a feature and upload at least one image.",
                { position: "top-right" }
            );
            return;
        }

        const formData = new FormData();
        formData.append("feature_id", feature_id);

        // Add selected groups to the form data for image association
        groupBackgrounds.forEach((group) => {
            formData.append("group_backgrounds[]", group);
        });

        // Add files (images) to the form data
        files.forEach((file) => {
            formData.append("images[]", file.file);
        });

        try {
            const response = await axios.post("/backgrounds", formData, {
                headers: { "Content-Type": "multipart/form-data" },
            });

            if (response.data.check) {
                toast.success("Images uploaded successfully!", {
                    position: "top-right",
                });
                setFiles([]); // Clear the files
                setData(response.data.data); // Update displayed images
            } else {
                throw new Error("Image upload failed");
            }
        } catch (error) {
            console.error("Error uploading images:", error);
            toast.error("Error uploading images.", {
                position: "top-right",
            });
        }
    };

    // Update the feature_id and clear selected groups when a new feature is chosen
    const handleFeatureChange = (e) => {
        setFeatureId(e.target.value);
        setGroupBackgrounds([]); // Clear selected groups
    };

    return (
        <div>
            <Layout>
                <ToastContainer />
                <div className="row">
                    <div className="col-md-4">
                        <div className="row">
                            <div className="col-md-12 mb-3">
                                <label htmlFor="">Feature</label>
                                <select
                                    value={feature_id}
                                    className="form-control"
                                    onChange={handleFeatureChange} // Use updated handler
                                >
                                    <option value={0} disabled>
                                        Select a feature
                                    </option>
                                    {features.map((feature) => (
                                        <option
                                            value={feature.id}
                                            key={feature.id}
                                        >
                                            {feature.name}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="col-md-12 mb-3">
                                <label htmlFor="">Group Background</label>
                                <div className="d-flex mb-2">
                                    <input
                                        type="text"
                                        className="form-control mr-2"
                                        value={newGroup}
                                        onChange={(e) =>
                                            setNewGroup(e.target.value)
                                        }
                                        placeholder="Enter new group name"
                                    />
                                    <button
                                        type="button"
                                        className="btn btn-success"
                                        onClick={handleAddGroup}
                                    >
                                        Add Group
                                    </button>
                                </div>

                                <ul className="list-group mb-3">
                                    {groupBackgrounds.map((group, index) => (
                                        <li key={index} className="group-item">
                                            {/* File Icon */}
                                            <div className="file-icon"></div>

                                            {/* Group Name */}
                                            <div className="group-name">
                                                {group}
                                            </div>

                                            {/* Remove Button */}
                                            <button
                                                type="button"
                                                className="btn-remove"
                                                onClick={() =>
                                                    handleRemoveGroup(group)
                                                }
                                            >
                                                Remove
                                            </button>
                                        </li>
                                    ))}
                                </ul>

                                <select
                                    className="form-control"
                                    onChange={(e) => {
                                        const selectedGroup = e.target.value;
                                        if (
                                            selectedGroup &&
                                            !groupBackgrounds.includes(
                                                selectedGroup
                                            )
                                        ) {
                                            setGroupBackgrounds((prev) => [
                                                ...prev,
                                                selectedGroup,
                                            ]);
                                        }
                                    }}
                                >
                                    <option value="">
                                        Select an existing group
                                    </option>
                                    {allGroups.map((group, index) => (
                                        <option key={index} value={group.name}>
                                            {group.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        <Dropzone
                            onChange={updateFiles}
                            value={files}
                            accept="image/*"
                            maxFiles={10}
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
