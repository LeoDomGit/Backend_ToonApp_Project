/* eslint-disable */
import * as React from 'react';
import Avatar from '@mui/material/Avatar';
import Button from '@mui/material/Button';
import CssBaseline from '@mui/material/CssBaseline';
import TextField from '@mui/material/TextField';
import FormControlLabel from '@mui/material/FormControlLabel';
import Checkbox from '@mui/material/Checkbox';
import Link from '@mui/material/Link';
import Paper from '@mui/material/Paper';
import Box from '@mui/material/Box';
import Grid from '@mui/material/Grid';
import LockOutlinedIcon from '@mui/icons-material/LockOutlined';
import Typography from '@mui/material/Typography';
import { createTheme, ThemeProvider } from '@mui/material/styles';
import { Notyf } from "notyf";
import 'notyf/notyf.min.css';
import axios from 'axios';
import { googleLogout, useGoogleLogin } from '@react-oauth/google';
const defaultTheme = createTheme();

export default function SignInSide() {
    const notyf = new Notyf({
        duration: 1000,
        position: {
            x: "right",
            y: "top",
        },
        types: [
            {
                type: "warning",
                background: "orange",
                icon: {
                    className: "material-icons",
                    tagName: "i",
                    text: "warning",
                },
            },
            {
                type: "error",
                background: "indianred",
                duration: 2000,
                dismissible: true,
            },
        ],
    });

    const handleSubmit = (event) => {
        event.preventDefault();
        const data = new FormData(event.currentTarget);
        const email = data.get('email');
        const password = data.get('password');

        if (email === '') {
            notyf.open({
                type: "error",
                message: "Email is required",
            });
        } else if (password === '') {
            notyf.open({
                type: "error",
                message: "Password is required",
            });
        } else {
            axios.post('/checkLoginAdmin', {
                email: email,
                password: password,
            }).then((res) => {
                if (res.data.check === true) {
                    notyf.open({
                        type: "success",
                        message: "Đăng nhập thành công",
                    });
                    setTimeout(() => {
                        window.location.replace('/users');
                    }, 2000);
                } else if (res.data.check === false) {
                    if (res.data.errors.password) {
                        notyf.open({
                            type: "error",
                            message: res.data.errors.password,
                        });
                    } else if (res.data.errors.name) {
                        notyf.open({
                            type: "error",
                            message: res.data.errors.name,
                        });
                    } else if (res.data.errors.email) {
                        notyf.open({
                            type: "error",
                            message: res.data.errors.email,
                        });
                    }
                }
            }).catch((error) => {
                notyf.open({
                    type: "error",
                    message: "Sai tên đăng nhập hoặc mật khẩu.",
                });
            });
        }
    };
    const [ user, setUser ] = React.useState([]);
    const [ profile, setProfile ] = React.useState([]);

    const login = useGoogleLogin({
        onSuccess: (codeResponse) => setUser(codeResponse),
        onError: (error) => console.log('Login Failed:', error)
    });
    React.useEffect(
        () => {
            if (user) {
                axios
                    .get(`https://www.googleapis.com/oauth2/v1/userinfo?access_token=${user.access_token}`, {
                        headers: {
                            Authorization: `Bearer ${user.access_token}`,
                            Accept: 'application/json'
                        }
                    })
                    .then((res) => {
                        console.log(res.data.email);
                        axios.post('/checkLoginEmail',{
                            email:res.data.email
                        }).then((res)=>{
                            if(res.data.check==true){
                                notyf.open({
                                    type: "success",
                                    message: "Đăng nhập thành công",
                                });
                                setTimeout(() => {
                                    window.location.replace('/users');
                                }, 2000);
                            }else if(res.data.check==false){
                              if(res.data.msg){
                                notyf.open({
                                  type: "error",
                                  message: res.data.msg,
                              });
                              }
                            }
                        })
                    })
                    .catch((err) => console.log(err));
            }
        },
        [ user ]
    );
    return (
        <ThemeProvider theme={defaultTheme}>
            <Grid container component="main" sx={{ height: '100vh' }}>
                <CssBaseline />
                <Grid
                    item
                    xs={false}
                    sm={4}
                    md={7}
                    sx={{
                        backgroundImage: 'url(https://images.unsplash.com/photo-1479762937580-3b682a10a0d8?fm=jpg&q=60&w=3000&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxleHBsb3JlLWZlZWR8Mnx8fGVufDB8fHx8fA%3D%3D)',
                        backgroundRepeat: 'no-repeat',
                        backgroundColor: (t) =>
                            t.palette.mode === 'light' ? t.palette.grey[50] : t.palette.grey[900],
                        backgroundSize: 'cover',
                        backgroundPosition: 'center',
                    }}
                />
                <Grid item xs={12} sm={8} md={5} component={Paper} elevation={6} square>
                    <Box
                        sx={{
                            my: 8,
                            mx: 4,
                            display: 'flex',
                            flexDirection: 'column',
                            alignItems: 'center',
                        }}
                    >
                        <Avatar sx={{ m: 1, bgcolor: 'secondary.main' }}>
                            <LockOutlinedIcon />
                        </Avatar>
                        <Typography component="h1" variant="h5">
                            Sign in
                        </Typography>
                        <Box component="form" noValidate onSubmit={handleSubmit} sx={{ mt: 1 }}>
                            <TextField
                                margin="normal"
                                required
                                fullWidth
                                id="email"
                                label="Email Address"
                                name="email"
                                autoComplete="email"
                                autoFocus
                            />
                            <TextField
                                margin="normal"
                                required
                                fullWidth
                                name="password"
                                label="Password"
                                type="password"
                                id="password"
                                autoComplete="current-password"
                            />
                            <FormControlLabel
                                control={<Checkbox value="remember" color="primary" />}
                                label="Remember me"
                            />
                            <Button
                                type="submit"
                                fullWidth
                                variant="contained"
                                sx={{ mt: 3, mb: 2 }}
                            >
                                Đăng nhập
                            </Button>
                            <button className='btn btn-outline-primary' onClick={login}>Sign in with Google 🚀 </button>
                            
                        </Box>
                    </Box>
                </Grid>
            </Grid>
        </ThemeProvider>
    );
}