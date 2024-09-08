<template>
  <AuthForm
    title="Welcome to Our Auction! 💰"
    description="Discover unique and valuable items up for bid. From rare collectibles to exclusive finds, there's something for everyone."
    submitText="Login"
    loadingText="Logging in..."
    :validationSchema="schema"
    :onSubmit="onSubmit"
  >
    <div class="col-span-6">
      <label for="Email" class="block text-sm font-medium text-gray-700">
        Email
      </label>
      <Field
        name="username"
        type="text"
        id="Email"
        :class="[
          'mt-1 w-full md:w-3/5 rounded-md text-sm shadow-sm block',
          errors.username ? 'border-red-600 bg-white' : 'border-gray-200 bg-white',
        ]"
      />
      <ErrorMessage name="username" class="text-red-600 text-sm" />
    </div>

    <div class="col-span-6">
      <label for="Password" class="block text-sm font-medium text-gray-700">
        Password
      </label>
      <Field
        name="password"
        type="password"
        id="Password"
        :class="[
          'mt-1 w-full md:w-3/5 rounded-md text-sm shadow-sm block',
          errors.password ? 'border-red-600 bg-white' : 'border-gray-200 bg-white',
        ]"
      />
      <ErrorMessage name="password" class="text-red-600 text-sm" />
    </div>

    <template #footer>
      Don't have an account?
      <RouterLink to="/register" class="text-gray-700 underline">Register</RouterLink>.
    </template>
  </AuthForm>
</template>

<script lang="ts" setup>
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/authStore';
import { Field, ErrorMessage, useForm } from 'vee-validate';
import * as yup from 'yup';
import AuthForm from './AuthForm.vue';

const router = useRouter();
const authStore = useAuthStore();

const schema = yup.object({
  username: yup.string().required('Email is required'),
  password: yup.string().required('Password is required'),
});

const { handleSubmit, errors } = useForm({
  validationSchema: schema,
});

const onSubmit = handleSubmit(async (values) => {
  const resp = await authStore.login(values.username, values.password);
  if (resp.success) {
    await authStore.fetchUserData();
    router.push('/');
  } else {
    throw new Error('Invalid username or password');
  }
});
</script>