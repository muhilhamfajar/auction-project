import axios from 'axios';
import { useAuthStore } from '@/stores/authStore';
import { useRouter } from 'vue-router';

const router = useRouter()

const axiosInstance = axios.create({
  baseURL: 'http://localhost/api',
});

axiosInstance.interceptors.request.use((config) => {
  const authStore = useAuthStore();
  const token = authStore.token;

  if (token) {
    config.headers['Authorization'] = `Bearer ${token}`;
  }

  return config;
}, (error) => {
  return Promise.reject(error);
});

axiosInstance.interceptors.response.use((response) => {
  return response;
}, (error) => {
  if (error.response.status === 401) {
    router.push('/login');
  }
  return Promise.reject(error);
});

export default axiosInstance;