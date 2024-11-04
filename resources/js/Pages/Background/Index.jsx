import React from 'react'
import { Dropzone, FileMosaic } from "@dropzone-ui/react";
function Index() {
    const [files, setFiles] = React.useState([]);
  const updateFiles = (incommingFiles) => {
    setFiles(incommingFiles);
  };
  return (
    <div>Index</div>
  )
}

export default Index