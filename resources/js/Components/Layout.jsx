/* eslint-disable */
import React from "react";
import { Sidebar, Menu, MenuItem, useProSidebar, SubMenu } from "react-pro-sidebar";
import MenuOutlinedIcon from "@mui/icons-material/MenuOutlined";
import GroupIcon from "@mui/icons-material/Group";
import CategoryIcon from "@mui/icons-material/Category";
import BrandingWatermarkIcon from "@mui/icons-material/BrandingWatermark";
import InventoryIcon from "@mui/icons-material/Inventory";
import LogoutIcon from "@mui/icons-material/Logout";
import CardTravelIcon from "@mui/icons-material/CardTravel";
import MapIcon from "@mui/icons-material/Map";
import DriveFileMoveIcon from "@mui/icons-material/DriveFileMove";
import DesignServicesTwoToneIcon from "@mui/icons-material/DesignServicesTwoTone";
import DialerSipIcon from "@mui/icons-material/DialerSip";
import CalendarMonthIcon from "@mui/icons-material/CalendarMonth";
import BookIcon from "@mui/icons-material/Book";
import CommentIcon from "@mui/icons-material/Comment";
import "../../css/app.css";
function Layout({ children }) {
	const { collapseSidebar } = useProSidebar();
	return (
		<>
			<div className="row w-100"></div>
			<div style={({ height: "90vh" }, { display: "flex" })}>
				<Sidebar style={{ minHeight: "90vh" }}>
					<Menu>
						<MenuItem
							icon={<MenuOutlinedIcon />}
							onClick={() => {
								collapseSidebar();
							}}
							style={{ textAlign: "center" }}>
							<h2>Admin</h2>
						</MenuItem>
						<SubMenu label="Tài khoản" icon={<GroupIcon />}>
							<a href={"/permissions"}>
								<MenuItem icon={<GroupIcon />}>Quyền tài khoản</MenuItem>
							</a>
							<a href={"/roles"}>
								<MenuItem icon={<GroupIcon />}>Loại tài khoản</MenuItem>
							</a>
							<a href={"/users"}>
								<MenuItem icon={<GroupIcon />}>Tài khoản</MenuItem>
							</a>
						</SubMenu>
						<SubMenu label="Features" icon={<GroupIcon />}>
							<a href={"/features"}>
								<MenuItem icon={<GroupIcon />}>Features</MenuItem>
							</a>
							<a href={"/sub_feature"}>
								<MenuItem icon={<GroupIcon />}>Sub Features</MenuItem>
							</a>
						</SubMenu>
						<a href={"/logout"}>
							<MenuItem icon={<LogoutIcon />}>Log out</MenuItem>
						</a>

						{/*             
            <MenuItem icon={<ReceiptOutlinedIcon />}>Profile</MenuItem>
            <MenuItem icon={<HelpOutlineOutlinedIcon />}>FAQ</MenuItem>
            <MenuItem icon={<CalendarTodayOutlinedIcon />}>Calendar</MenuItem> */}
					</Menu>
				</Sidebar>
				<main className="p-4 w-100">{children}</main>
			</div>
		</>
	);
}

export default Layout;
