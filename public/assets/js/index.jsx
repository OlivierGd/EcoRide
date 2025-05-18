import React from "react";
import {createRoot} from "react-dom/client";
import Greeting from "./greeting";


const rootElement = document.getElementById("root");
const root = createRoot(rootElement);
root.render(<Greeting/>, rootElement);
