/* eslint-disable */
import React from "react";
import {
    Sidebar,
    Menu,
    MenuItem,
    useProSidebar,
    SubMenu,
} from "react-pro-sidebar";
import MenuOutlinedIcon from "@mui/icons-material/MenuOutlined";
import GroupIcon from "@mui/icons-material/Group";
import BrandingWatermarkIcon from "@mui/icons-material/BrandingWatermark";
import InventoryIcon from "@mui/icons-material/Inventory";
import KeyIcon from "@mui/icons-material/Key";
import LogoutIcon from "@mui/icons-material/Logout";
import WallpaperIcon from "@mui/icons-material/Wallpaper";
import FeedbackIcon from "@mui/icons-material/Feedback";
import PhotoSizeSelectActualIcon from "@mui/icons-material/PhotoSizeSelectActual";
import FeaturedPlayListIcon from "@mui/icons-material/FeaturedPlayList";
import FilterIcon from "@mui/icons-material/Filter";
import "../../css/app.css";
import PermDataSettingIcon from "@mui/icons-material/PermDataSetting";
function Layout({ children }) {
    const { collapseSidebar } = useProSidebar();

    return (
        <>
            <div className="row w-100"></div>
            <div style={{ display: "flex", height: "90vh" }}>
                <Sidebar style={{ minHeight: "90vh" }}>
                    <Menu>
                        <MenuItem
                            icon={<MenuOutlinedIcon />}
                            onClick={() => {
                                collapseSidebar();
                            }}
                            style={{ textAlign: "center" }}
                        >
                            <h2>Admin</h2>
                        </MenuItem>
                        <SubMenu label="Tài khoản" icon={<GroupIcon />}>
                            <a href={"/permissions"}>
                                <MenuItem icon={<GroupIcon />}>
                                    Quyền tài khoản
                                </MenuItem>
                            </a>
                            <a href={"/roles"}>
                                <MenuItem icon={<GroupIcon />}>
                                    Loại tài khoản
                                </MenuItem>
                            </a>
                            <a href={"/users"}>
                                <MenuItem icon={<GroupIcon />}>
                                    Tài khoản
                                </MenuItem>
                            </a>
                        </SubMenu>
                        <SubMenu
                            label="Features"
                            icon={<FeaturedPlayListIcon />}
                        >
                            <a href={"/features"}>
                                <MenuItem icon={<FeaturedPlayListIcon />}>
                                    Features
                                </MenuItem>
                            </a>
                            <a href={"/sub_feature"}>
                                <MenuItem icon={<FeaturedPlayListIcon />}>
                                    Sub Features
                                </MenuItem>
                            </a>
                            <a href={"/api_images"}>
                                <MenuItem icon={<PhotoSizeSelectActualIcon />}>
                                    API Images
                                </MenuItem>
                            </a>
                        </SubMenu>
                        <a href={"/sizes"}>
                            <MenuItem icon={<BrandingWatermarkIcon />}>
                                Sizes
                            </MenuItem>
                        </a>
                        <a href={"/backgrounds"}>
                            <MenuItem icon={<WallpaperIcon />}>
                                Background
                            </MenuItem>
                        </a>
                        <a href={"/effects"}>
                            <MenuItem icon={<FilterIcon />}>Effects</MenuItem>
                        </a>
                        <a href={"/keys"}>
                            <MenuItem icon={<KeyIcon />}>API key</MenuItem>
                        </a>
                        <a href={"/packages"}>
                            <MenuItem icon={<InventoryIcon />}>
                                Packages
                            </MenuItem>
                        </a>
                        <a href={"/configs"}>
                            <MenuItem icon={<PermDataSettingIcon />}>
                                Configs
                            </MenuItem>
                        </a>
                        <a href={"/feedback"}>
                            <MenuItem icon={<FeedbackIcon />}>
                                Feedback
                            </MenuItem>
                        </a>
                        <a href={"/logout"}>
                            <MenuItem icon={<LogoutIcon />}>Log out</MenuItem>
                        </a>
                    </Menu>
                </Sidebar>
                <main className="p-4 w-85">{children}</main>
            </div>
        </>
    );
}

export default Layout;
