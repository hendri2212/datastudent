<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import {
    GraduationCap,
    Lock,
    Mail,
    ArrowRight,
    ShieldCheck,
    Eye,
    EyeOff,
} from 'lucide-vue-next';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PasskeyVerify from '@/components/PasskeyVerify.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: undefined,
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const showPassword = ref(false);
</script>

<template>
    <Head title="Masuk Akun" />

    <div class="flex min-h-screen w-full flex-col justify-between bg-neutral-100 font-sans text-black antialiased">
        <!-- HEADER -->
        <header class="flex w-full items-center justify-between border-b border-neutral-200 bg-white px-8 py-5 shadow-sm md:px-16">
            <div class="flex items-center gap-3.5">
                <div class="rounded-xl bg-black p-2.5 text-white shadow-sm">
                    <GraduationCap class="size-6" />
                </div>
                <div>
                    <span class="block text-base font-black tracking-wider uppercase leading-none">
                        Sistem <span class="font-light">Pendaftaran</span>
                    </span>
                    <span class="text-xs font-medium text-neutral-500">Portal Akses Pengguna</span>
                </div>
            </div>
        </header>

        <!-- MAIN CONTAINER (Diperlebar ke max-w-6xl) -->
        <main class="mx-auto flex w-full max-w-6xl flex-1 flex-col justify-center px-4 py-12 sm:px-6 lg:px-8">
            <div class="w-full rounded-3xl border border-neutral-200 bg-white p-8 shadow-2xl space-y-8 sm:p-12 md:p-16">
                <div class="space-y-2 text-center">
                    <div class="inline-flex items-center gap-2 rounded-full bg-neutral-100 px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-neutral-700">
                        <ShieldCheck class="size-4 text-black" />
                        Autentikasi Terpadu
                    </div>
                    <h1 class="text-3xl font-black tracking-tight md:text-5xl">Selamat Datang</h1>
                    <p class="text-base text-neutral-500">
                        Masukkan email dan kata sandi Anda untuk mengakses portal pendaftaran.
                    </p>
                </div>

                <div v-if="status" class="w-full rounded-xl bg-emerald-50 p-4 text-sm font-medium text-emerald-700 border border-emerald-200">
                    {{ status }}
                </div>

                <!-- KOMPONEN PASSKEY -->
                <div class="w-full">
                    <PasskeyVerify />
                </div>

                <Form
                    v-bind="store.form()"
                    :reset-on-success="['password']"
                    v-slot="{ errors, processing }"
                    class="w-full space-y-6"
                >
                    <!-- Email Field -->
                    <div class="w-full space-y-2">
                        <Label for="email" class="flex items-center gap-2 text-sm font-bold text-neutral-700">
                            <Mail class="size-4 text-black" /> Email Address
                        </Label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            required
                            autofocus
                            :tabindex="1"
                            autocomplete="email"
                            placeholder="nama@domain.com"
                            class="block h-12 w-full rounded-xl border border-neutral-300 bg-neutral-50 px-4 text-sm text-black transition-colors focus:border-black focus:bg-white focus:outline-none"
                        />
                        <InputError :message="errors.email" />
                    </div>

                    <!-- Password Field -->
                    <div class="w-full space-y-2">
                        <div class="flex items-center justify-between">
                            <Label for="password" class="flex items-center gap-2 text-sm font-bold text-neutral-700">
                                <Lock class="size-4 text-black" /> Kata Sandi
                            </Label>
                            <TextLink
                                v-if="canResetPassword"
                                :href="request()"
                                class="text-xs font-medium text-neutral-500 hover:text-black hover:underline"
                                :tabindex="5"
                            >
                                Lupa sandi?
                            </TextLink>
                        </div>
                        <div class="relative w-full">
                            <input
                                id="password"
                                name="password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                :tabindex="2"
                                autocomplete="current-password"
                                placeholder="••••••••"
                                class="block h-12 w-full rounded-xl border border-neutral-300 bg-neutral-50 pl-4 pr-12 text-sm text-black transition-colors focus:border-black focus:bg-white focus:outline-none"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-neutral-400 hover:text-black"
                            >
                                <Eye v-if="!showPassword" class="size-5" />
                                <EyeOff v-else class="size-5" />
                            </button>
                        </div>
                        <InputError :message="errors.password" />
                    </div>

                    <!-- Remember Me Option -->
                    <div class="flex items-center justify-between pt-1">
                        <Label for="remember" class="flex items-center gap-2.5 text-sm font-medium text-neutral-600 cursor-pointer">
                            <Checkbox id="remember" name="remember" :tabindex="3" class="size-4 rounded border-neutral-300 text-black focus:ring-black" />
                            <span>Ingat saya di perangkat ini</span>
                        </Label>
                    </div>

                    <!-- Submit Button -->
                    <Button
                        type="submit"
                        :tabindex="4"
                        :disabled="processing"
                        data-test="login-button"
                        class="flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-black text-sm font-bold tracking-wider text-white uppercase shadow-lg transition-all hover:bg-neutral-800 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <Spinner v-if="processing" />
                        <span v-if="!processing">Masuk Ke Portal</span>
                        <ArrowRight v-if="!processing" class="size-5" />
                    </Button>
                </Form>

                <!-- Footer Sign up Link -->
                <div class="w-full rounded-2xl border border-neutral-200 bg-neutral-50 p-5 text-center text-sm">
                    <span class="text-neutral-500">Belum memiliki akun pendaftaran?</span>
                    <TextLink :href="register()" :tabindex="5" class="ml-1.5 font-bold text-black hover:underline">
                        Daftar Baru
                    </TextLink>
                </div>
            </div>
        </main>

        <!-- FOOTER -->
        <footer class="w-full py-6 text-center border-t border-neutral-200 bg-white">
            <p class="text-xs font-bold tracking-widest text-neutral-400 uppercase">
                &copy; SISTEM INFORMASI AKADEMIK &bull; ALL RIGHTS RESERVED
            </p>
        </footer>
    </div>
</template>