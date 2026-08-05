<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineOptions({
    layout: undefined,
});

defineProps<{
    passwordRules: string;
}>();
</script>

<template>
    <Head title="Daftar Akun" />

    <div class="flex min-h-screen w-full flex-col justify-between bg-neutral-100 font-sans text-black antialiased">
        <header class="flex w-full items-center justify-between border-b border-neutral-200 bg-white px-8 py-5 shadow-sm md:px-16">
            <div class="flex items-center gap-3.5">
                <div class="rounded-xl bg-black p-2.5 text-white shadow-sm">
                    <span class="text-base font-black">S</span>
                </div>
                <div>
                    <span class="block text-base font-black tracking-wider uppercase leading-none">
                        Sistem <span class="font-light">Pendaftaran</span>
                    </span>
                    <span class="text-xs font-medium text-neutral-500">Portal Akses Pengguna</span>
                </div>
            </div>
        </header>

        <main class="mx-auto flex w-full max-w-6xl flex-1 flex-col justify-center px-4 py-12 sm:px-6 lg:px-8">
            <div class="w-full rounded-3xl border border-neutral-200 bg-white p-8 shadow-2xl space-y-8 sm:p-12 md:p-16">
                <div class="space-y-2 text-center">
                    <div class="inline-flex items-center gap-2 rounded-full bg-neutral-100 px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-neutral-700">
                        Registrasi
                    </div>
                    <h1 class="text-3xl font-black tracking-tight md:text-5xl">Buat Akun Baru</h1>
                    <p class="text-base text-neutral-500">
                        Daftar sebagai siswa untuk mengakses portal pendaftaran.
                    </p>
                </div>

                <Form
                    v-bind="store.form()"
                    :reset-on-success="['password', 'password_confirmation']"
                    v-slot="{ errors, processing }"
                    class="w-full space-y-6"
                >
                    <div class="grid gap-6">
                        <div class="grid gap-2">
                            <Label for="name" class="text-sm font-bold text-neutral-700">Nama Lengkap</Label>
                            <Input
                                id="name"
                                type="text"
                                required
                                autofocus
                                :tabindex="1"
                                autocomplete="name"
                                name="name"
                                placeholder="Nama lengkap"
                            />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="email" class="text-sm font-bold text-neutral-700">Alamat Email</Label>
                            <Input
                                id="email"
                                type="email"
                                required
                                :tabindex="2"
                                autocomplete="email"
                                name="email"
                                placeholder="nama@domain.com"
                            />
                            <InputError :message="errors.email" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="password" class="text-sm font-bold text-neutral-700">Kata Sandi</Label>
                            <PasswordInput
                                id="password"
                                required
                                :tabindex="3"
                                autocomplete="new-password"
                                name="password"
                                placeholder="••••••••"
                                :passwordrules="passwordRules"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="password_confirmation" class="text-sm font-bold text-neutral-700">Konfirmasi Kata Sandi</Label>
                            <PasswordInput
                                id="password_confirmation"
                                required
                                :tabindex="4"
                                autocomplete="new-password"
                                name="password_confirmation"
                                placeholder="••••••••"
                                :passwordrules="passwordRules"
                            />
                            <InputError :message="errors.password_confirmation" />
                        </div>
                    </div>

                    <Button
                        type="submit"
                        :tabindex="5"
                        :disabled="processing"
                        class="flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-black text-sm font-bold tracking-wider text-white uppercase shadow-lg transition-all hover:bg-neutral-800 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <Spinner v-if="processing" />
                        <span v-if="!processing">Daftar Akun</span>
                    </Button>
                </Form>

                <div class="w-full rounded-2xl border border-neutral-200 bg-neutral-50 p-5 text-center text-sm">
                    <span class="text-neutral-500">Sudah mempunyai akun?</span>
                    <TextLink :href="login()" :tabindex="6" class="ml-1.5 font-bold text-black hover:underline">
                        Masuk
                    </TextLink>
                </div>
            </div>
        </main>

        <footer class="w-full py-6 text-center border-t border-neutral-200 bg-white">
            <p class="text-xs font-bold tracking-widest text-neutral-400 uppercase">
                &copy; SISTEM INFORMASI AKADEMIK &bull; ALL RIGHTS RESERVED
            </p>
        </footer>
    </div>
</template>
