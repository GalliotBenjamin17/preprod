<x-guest-layout>
    <div class="flex min-h-full">
        <div @class([
            "flex flex-1 flex-col justify-center py-12 px-4 sm:px-6 lg:px-20 xl:px-24",
            "lg:flex-none" => !request()->has('fullPage')
        ])>
            <div class="mx-auto w-full max-w-sm lg:w-96">
                <div>
                    <img class="h-12 w-auto" src="{{ $tenant ? asset($tenant->logo) : asset('img/logos/cooperative-carbone/main.svg') }}">
                    <h2 class="mt-6 text-3xl font-semibold tracking-tight text-gray-800">Créer un compte</h2>

                </div>

                <div class="mt-12">
                    @if(Session::has('success'))
                        <x-information-alert
                            type="success"
                            :message="Session::get('success')"
                        />
                    @else
                        <form method="POST" action="{{ route('tenant.register', ['tenant' => $tenant]) }}">
                            @csrf
                            <x-honeypot />

                            <div class="grid grid-cols-2 gap-5">
                                <div>
                                    <x-label for="first_name" value="Prénom" required/>
                                    <x-input id="first_name" type="text" name="first_name" autocomplete="given-name" :value="old('first_name')" required />
                                    <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
                                </div>
                                <div>
                                    <x-label for="last_name" value="Nom" required/>
                                    <x-input id="last_name" type="text" name="last_name" autocomplete="family-name" :value="old('last_name')" required />
                                    <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
                                </div>

                                <div class="col-span-2">
                                    <x-label for="email" value="Email" required/>
                                    <x-input id="email" type="email" name="email" autocomplete="email" autocomplete="email" :value="old('email')" required />
                                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                                </div>

                                <div class="col-span-2" x-data="{clear:false}">
                                    <div class="flex items-center justify-between">
                                        <x-label for="phone" value="Mot de passe" required/>
                                        <div>
                                            <a x-on:click="clear = !clear">
                                                <svg x-cloak x-show="!clear" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-gray-500">
                                                    <path d="M10 12.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z" />
                                                    <path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 010-1.186A10.004 10.004 0 0110 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0110 17c-4.257 0-7.893-2.66-9.336-6.41zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                                </svg>
                                                <svg x-cloak x-show="clear" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-gray-500">
                                                    <path fill-rule="evenodd" d="M3.28 2.22a.75.75 0 00-1.06 1.06l14.5 14.5a.75.75 0 101.06-1.06l-1.745-1.745a10.029 10.029 0 003.3-4.38 1.651 1.651 0 000-1.185A10.004 10.004 0 009.999 3a9.956 9.956 0 00-4.744 1.194L3.28 2.22zM7.752 6.69l1.092 1.092a2.5 2.5 0 013.374 3.373l1.091 1.092a4 4 0 00-5.557-5.557z" clip-rule="evenodd" />
                                                    <path d="M10.748 13.93l2.523 2.523a9.987 9.987 0 01-3.27.547c-4.258 0-7.894-2.66-9.337-6.41a1.651 1.651 0 010-1.186A10.007 10.007 0 012.839 6.02L6.07 9.252a4 4 0 004.678 4.678z" />
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                    <x-input id="phone" x-bind:type="clear ? 'text' : 'password'" name="password" :value="old('password')" autocomplete="current-password" required />
                                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                                </div>

                                <div class="col-span-2 relative flex items-start">
                                    <div class="flex h-6 items-center">
                                        <input id="conditions" aria-describedby="conditions-description" name="conditions" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600" required>
                                    </div>
                                    <div class="ml-3 text-sm leading-6">
                                        <span id="conditions-description" class="text-sm text-gray-500">J'accepte les <a href="{{ $tenant->cgu }}" target="_blank" class="text-blue-600  hover:text-blue-700 hover:underline font-semibold">Conditions Générales d'Utilisation</a>.</span>
                                    </div>
                                </div>

                            </div>


                            <div class="flex items-center mt-4">
                                <x-button type="success" class="w-full !bg-[#244999]"  size="lg" submit>
                                    Créer un compte
                                </x-button>
                            </div>

                            <a href="{{ route('login') }}" class="flex items-center justify-center mt-5 text-[#244999] font-medium text-sm">
                                <span>Vous avez déjà un compte ? Connectez-vous</span>
                                <x-icon.chevron_droite class="h-5 w-5" />
                            </a>
                        </form>
                    @endif
                </div>
            </div>
        </div>
        <div @class([
            "relative hidden w-0 flex-1 lg:block",
            "!hidden lg:!hidden" => request()->has('fullPage')
        ])>
            @if($tenant and $tenant->login_image)
                <img class="absolute bg-gray-100 inset-0 h-full w-full object-cover" src="{{ asset($tenant->login_image) }}" alt="">
            @else
                <img class="absolute bg-blue-100 inset-0 h-full w-full object-contain p-10" src="{{ asset('img/illustrations/cooperative-carbone/Coop_confiance_without_logos.svg') }}" alt="">
            @endif
        </div>
    </div>
</x-guest-layout>
