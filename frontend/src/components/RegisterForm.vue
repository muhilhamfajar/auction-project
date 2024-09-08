<template>
  <AuthForm
    title="Join Our Auction Community! 🎉"
    description="Sign up to start bidding on unique and valuable items. Create your account and dive into a world of exciting auctions!"
    submitText="Create Account"
    loadingText="Creating Account..."
    :validationSchema="schema"
    :onSubmit="onSubmit"
  >
    <div class="col-span-6 sm:col-span-3">
      <label for="Name" class="block text-sm font-medium text-gray-700">
        Name
      </label>
      <Field
        name="name"
        type="text"
        id="Name"
        :class="[
          'mt-1 w-full rounded-md text-sm shadow-sm',
          errors.name ? 'border-red-600 bg-white' : 'border-gray-200 bg-white',
        ]"
      />
      <ErrorMessage name="name" class="text-red-600 text-sm" />
    </div>

    <div class="col-span-6 sm:col-span-3">
      <label for="Email" class="block text-sm font-medium text-gray-700">
        Email
      </label>
      <Field
        name="username"
        type="email"
        id="Email"
        :class="[
          'mt-1 w-full rounded-md text-sm shadow-sm',
          errors.username || duplicateError
            ? 'border-red-600 bg-white'
            : 'border-gray-200 bg-white',
        ]"
      />
      <ErrorMessage name="username" class="text-red-600 text-sm" />
      <span v-if="duplicateError" class="text-red-600 text-sm">{{ duplicateError }}</span>
    </div>

    <div class="col-span-6 sm:col-span-3">
      <label for="Password" class="block text-sm font-medium text-gray-700">
        Password
      </label>
      <Field
        name="password"
        type="password"
        id="Password"
        :class="[
          'mt-1 w-full rounded-md text-sm shadow-sm',
          errors.password
            ? 'border-red-600 bg-white'
            : 'border-gray-200 bg-white',
        ]"
      />
      <ErrorMessage name="password" class="text-red-600 text-sm" />
    </div>

    <div class="col-span-6 sm:col-span-3">
      <label
        for="PasswordConfirmation"
        class="block text-sm font-medium text-gray-700"
      >
        Confirm Password
      </label>
      <Field
        name="passwordConfirmation"
        type="password"
        id="PasswordConfirmation"
        :class="[
          'mt-1 w-full rounded-md text-sm shadow-sm',
          errors.passwordConfirmation
            ? 'border-red-600 bg-white'
            : 'border-gray-200 bg-white',
        ]"
      />
      <ErrorMessage name="passwordConfirmation" class="text-red-600 text-sm" />
    </div>

    <template #footer>
      Already have an account?
      <RouterLink to="/login" class="text-gray-700 underline">Log in</RouterLink
      >.
    </template>
  </AuthForm>
</template>

<script lang="ts" setup>
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/authStore";
import { Field, ErrorMessage, useForm } from "vee-validate";
import * as yup from "yup";
import AuthForm from "./AuthForm.vue";
import { ref } from "vue";

const router = useRouter();
const authStore = useAuthStore();
const duplicateError = ref("");

const schema = yup.object({
  name: yup.string().required("Name is required"),
  username: yup.string().email("Invalid email").required("Email is required"),
  password: yup
    .string()
    .min(6, "Password must be at least 6 characters")
    .required("Password is required"),
  passwordConfirmation: yup
    .string()
    .oneOf([yup.ref("password")], "Passwords must match")
    .required("Password confirmation is required"),
});

const { handleSubmit, errors } = useForm({
  validationSchema: schema,
});

const onSubmit = handleSubmit(async (values) => {
  try {
    duplicateError.value = "";
    await authStore.register(
      values.name,
      values.username,
      values.password,
      values.passwordConfirmation
    );
    const loginSuccess = await authStore.login(
      values.username,
      values.password
    );
    if (loginSuccess) {
      await authStore.fetchUserData();
      router.push("/");
    } else {
      throw new Error(
        "Registration successful, but unable to log in. Please try logging in manually."
      );
    }
  } catch (error) {
    if (error.response && error.response.status === 409) {
      duplicateError.value =
        "This email is already registered. Please use a different email.";
    } else {
      console.error("Registration error:", error);
      throw new Error(
        "An error occurred during registration. Please try again."
      );
    }
  }
});
</script>
