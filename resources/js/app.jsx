import React from "react";
import { createInertiaApp } from "@inertiajs/react";
import { createRoot } from "react-dom/client";
import { ProSidebarProvider } from "react-pro-sidebar";
import "bootstrap/dist/css/bootstrap.min.css";
import "bootstrap/dist/js/bootstrap.min.js";
import '../css/app.css';
import { GoogleOAuthProvider } from '@react-oauth/google';
createInertiaApp({
	resolve: (name) => {
		const pages = import.meta.glob("./Pages/**/*.jsx", { eager: true });
		return pages[`./Pages/${name}.jsx`];
	},
	setup({ el, App, props }) {
		const root = createRoot(el);
		root.render(
			<GoogleOAuthProvider clientId={import.meta.env.VITE_GOOGLE_CLIENT_ID}>
			<ProSidebarProvider>
				<App {...props} />
			</ProSidebarProvider>
			</GoogleOAuthProvider>
			,
		);
	},
});