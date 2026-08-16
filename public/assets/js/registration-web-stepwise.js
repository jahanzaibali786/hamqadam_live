(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var flow = document.querySelector('[data-registration-flow]');
        if (!flow) return;

        var config = window.registrationStepwiseConfig || {};
        var form = document.getElementById('reg-form');
        var steps = Array.from(flow.querySelectorAll('[data-registration-step]'));
        var current = 1;
        var total = Number(flow.dataset.totalSteps || steps.length);
        var prev = flow.querySelector('[data-registration-prev]');
        var next = flow.querySelector('[data-registration-next]');
        var submit = document.getElementById('createAccountBtn');
        var terms = document.getElementById('registrationTermsBlock');
        var heading = flow.querySelector('[data-registration-heading]');
        var subheading = flow.querySelector('[data-registration-subheading]');
        var isSubmitting = false;

        function setupDropdownStacking() {
            if (!window.jQuery) return;
            $(flow)
                .on('shown.bs.select', '.aiz-selectpicker', function () {
                    flow.classList.add('is-select-open');
                    var group = this.closest('.form-group');
                    if (group) group.classList.add('is-select-open');
                })
                .on('hidden.bs.select', '.aiz-selectpicker', function () {
                    var group = this.closest('.form-group');
                    if (group) group.classList.remove('is-select-open');
                    if (!flow.querySelector('.form-group.is-select-open')) {
                        flow.classList.remove('is-select-open');
                    }
                });
        }
        function refreshSelects() {
            if (window.jQuery && $.fn.selectpicker) {
                $(flow).find('.aiz-selectpicker').selectpicker('refresh');
            }
        }

        function notify(message) {
            if (window.AIZ && AIZ.plugins && AIZ.plugins.notify) {
                AIZ.plugins.notify('danger', message);
                return;
            }
            alert(message);
        }

        function postJson(url, payload) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrf || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            }).then(function (response) {
                return response.json();
            });
        }

        function responseItems(data) {
            if (Array.isArray(data)) return data;
            if (data && Array.isArray(data.data)) return data.data;
            return [];
        }

        function fillSelect(select, placeholder, items, selected) {
            if (!select) return;
            select.innerHTML = '<option value="">' + placeholder + '</option>';
            items.forEach(function (item) {
                var option = document.createElement('option');
                var value = item.id !== undefined ? item.id : (item.value !== undefined ? item.value : item.name);
                option.value = value;
                option.textContent = item.name || item.label || value;
                if (String(selected || '') === String(value)) option.selected = true;
                select.appendChild(option);
            });
            refreshSelects();
        }


        function loadInstitutionsForSelectedCountry() {
            var country = flow.querySelector('[data-location-country="profile"]');
            var institution = flow.querySelector('[data-education-institution="1"]');
            if (!institution) return;
            if (!country || !country.value) {
                fillSelect(institution, config.messages.selectCountryForInstitution || 'Select country first', [], '');
                return;
            }

            var selectedCountry = country.options[country.selectedIndex];
            var countryName = selectedCountry ? selectedCountry.textContent.trim().toLowerCase() : '';
            var names = (config.institutionsByCountry && config.institutionsByCountry[countryName]) || ['Other'];
            fillSelect(institution, config.messages.chooseInstitution || 'Choose College / University', names.map(function (name) {
                return { value: name, name: name };
            }), institution.dataset.selected || '');
            institution.dataset.selected = '';
        }
        function setupLocation(group) {
            var country = flow.querySelector('[data-location-country="' + group + '"]');
            var state = flow.querySelector('[data-location-state="' + group + '"]');
            var city = flow.querySelector('[data-location-city="' + group + '"]');
            var area = flow.querySelector('[data-location-area="' + group + '"]');
            if (!country || !state || !city || !config.routes) return;

            function loadAreas() {
                if (!area) return;
                var selectedOption = city.options[city.selectedIndex];
                var cityName = selectedOption ? selectedOption.textContent.trim().toLowerCase() : '';
                var areaNames = (config.areasByCity && config.areasByCity[cityName]) || ['Other'];
                fillSelect(area, config.messages.chooseArea, areaNames.map(function (name) {
                    return { value: name, name: name };
                }), area.dataset.selected || '');
                area.dataset.selected = '';
            }

            function resetArea() {
                if (area) fillSelect(area, config.messages.selectCityFirst, [], '');
            }

            function loadStates() {
                fillSelect(city, config.messages.selectStateFirst, [], '');
                resetArea();
                if (!country.value) {
                    fillSelect(state, config.messages.selectCountryFirst, [], '');
                    return;
                }

                fillSelect(state, config.messages.loadingStates, [], '');
                postJson(config.routes.states, { country_id: country.value })
                    .then(function (data) {
                        fillSelect(state, config.messages.chooseState, responseItems(data), state.dataset.selected || '');
                        state.dataset.selected = '';
                        if (state.value) loadCities();
                    })
                    .catch(function () {
                        fillSelect(state, config.messages.unableStates, [], '');
                    });
            }

            function loadCities() {
                resetArea();
                if (!state.value) {
                    fillSelect(city, config.messages.selectStateFirst, [], '');
                    return;
                }

                fillSelect(city, config.messages.loadingCities, [], '');
                postJson(config.routes.cities, { state_id: state.value })
                    .then(function (data) {
                        fillSelect(city, config.messages.chooseCity, responseItems(data), city.dataset.selected || '');
                        city.dataset.selected = '';
                        loadAreas();
                    })
                    .catch(function () {
                        fillSelect(city, config.messages.unableCities, [], '');
                    });
            }

            country.addEventListener('change', function () {
                loadStates();
                if (group === 'profile') loadInstitutionsForSelectedCountry();
            });
            state.addEventListener('change', loadCities);
            city.addEventListener('change', loadAreas);
            if (country.value) loadStates();
            if (group === 'profile') loadInstitutionsForSelectedCountry();
        }

        function setupCaste() {
            var religion = document.getElementById('registration_religion_id');
            var caste = document.getElementById('registration_caste_id');
            var subCaste = document.getElementById('registration_sub_caste_id');
            if (!religion || !caste || !subCaste || !config.routes) return;

            function loadCastes() {
                if (!religion.value) {
                    fillSelect(caste, config.messages.chooseCaste, config.allCastes || [], caste.dataset.selected || caste.value || '');
                    loadSubCastes();
                    return;
                }

                postJson(config.routes.castes, { religion_id: religion.value }).then(function (data) {
                    var casteItems = responseItems(data);
                    if (!casteItems.length) casteItems = config.allCastes || [];
                    fillSelect(caste, config.messages.chooseCaste, casteItems, caste.value || caste.dataset.selected || '');
                    loadSubCastes();
                }).catch(function () {
                    fillSelect(caste, config.messages.chooseCaste, config.allCastes || [], caste.dataset.selected || caste.value || '');
                    loadSubCastes();
                });
            }

            function loadSubCastes() {
                if (!caste.value) {
                    fillSelect(subCaste, config.messages.chooseSubCaste, [], '');
                    return;
                }

                postJson(config.routes.subCastes, { caste_id: caste.value }).then(function (data) {
                    fillSelect(subCaste, config.messages.chooseSubCaste, responseItems(data), subCaste.dataset.selected || '');
                    subCaste.dataset.selected = '';
                }).catch(function () {
                    fillSelect(subCaste, config.messages.chooseSubCaste, [], '');
                });
            }

            religion.addEventListener('change', loadCastes);
            caste.addEventListener('change', loadSubCastes);
            loadCastes();
        }

        // New functions for controlled dropdowns
        function setupProfessionDropdowns() {
            var professionCategory = flow.querySelector('[data-profession-category="1"]');
            var profession = flow.querySelector('[data-profession="1"]');
            if (!professionCategory || !profession || !config.routes) return;

            function loadProfessions() {
                if (!professionCategory.value) {
                    fillSelect(profession, config.messages.selectProfessionCategory || 'Select profession category first', [], '');
                    return;
                }

                fillSelect(profession, 'Loading professions...', [], '');
                postJson(config.routes.professions, { category_id: professionCategory.value })
                    .then(function (data) {
                        fillSelect(profession, 'Choose Profession', responseItems(data.professions), profession.dataset.selected || '');
                        profession.dataset.selected = '';
                    })
                    .catch(function () {
                        fillSelect(profession, 'Unable to load professions', [], '');
                    });
            }

            professionCategory.addEventListener('change', function () {
                loadProfessions();
            });
            if (professionCategory.value) loadProfessions();
        }

        function setupEducationDropdowns() {
            var educationLevel = flow.querySelector('[data-education-level="1"]');
            var degree = flow.querySelector('[data-degree="1"]');
            var fieldOfStudy = flow.querySelector('[data-field-of-study="1"]');
            var institution = flow.querySelector('[data-institution="1"]');
            var educationStatus = flow.querySelector('[data-education-status="1"]');
            var expectedGradYear = flow.querySelector('[data-education-status-dependent="1"]');
            
            console.log('setupEducationDropdowns - Found elements:', {
                educationLevel: !!educationLevel,
                degree: !!degree,
                fieldOfStudy: !!fieldOfStudy,
                institution: !!institution,
                educationStatus: !!educationStatus,
                expectedGradYear: !!expectedGradYear
            });
            
            if (!educationLevel || !degree || !config.routes) {
                console.log('setupEducationDropdowns - Missing required elements or routes');
                return;
            }

            function loadDegrees() {
                console.log('loadDegrees called - educationLevel.value:', educationLevel ? educationLevel.value : 'null');
                if (!educationLevel.value) {
                    fillSelect(degree, config.messages.selectEducationLevel || 'Select education level first', [], '');
                    resetFieldOfStudy();
                    // Don't reset institution - only change on country change
                    return;
                }

                console.log('loadDegrees - Calling AJAX with education_level_id:', educationLevel.value);
                fillSelect(degree, 'Loading degrees...', [], '');
                postJson(config.routes.degrees, { education_level_id: educationLevel.value })
                    .then(function (data) {
                        console.log('loadDegrees - AJAX response:', data);
                        var degrees = responseItems(data.degrees);
                        console.log('loadDegrees - Degrees count:', degrees.length);
                        if (degrees.length === 0) {
                            console.log('loadDegrees - No degrees found, loading all');
                            // If no degrees for this education level, load all degrees
                            postJson(config.routes.degrees, {})
                                .then(function (allData) {
                                    console.log('loadDegrees - All degrees response:', allData);
                                    fillSelect(degree, 'Choose Degree', responseItems(allData.degrees), degree.dataset.selected || '');
                                    degree.dataset.selected = '';
                                    if (degree.value) loadFieldsOfStudy();
                                })
                                .catch(function (error) {
                                    console.error('loadDegrees - Error loading all degrees:', error);
                                    fillSelect(degree, 'Unable to load degrees', [], '');
                                });
                        } else {
                            fillSelect(degree, 'Choose Degree', degrees, degree.dataset.selected || '');
                            degree.dataset.selected = '';
                            if (degree.value) loadFieldsOfStudy();
                        }
                    })
                    .catch(function (error) {
                        console.error('loadDegrees - AJAX error:', error);
                        fillSelect(degree, 'Unable to load degrees', [], '');
                    });
            }

            function loadFieldsOfStudy() {
                console.log('loadFieldsOfStudy called - degree.value:', degree ? degree.value : 'null');
                // Don't reset institution - only change on country change
                if (!degree.value) {
                    fillSelect(fieldOfStudy, config.messages.selectDegree || 'Select degree first', [], '');
                    return;
                }

                console.log('loadFieldsOfStudy - Calling AJAX with degree_id:', degree.value);
                fillSelect(fieldOfStudy, 'Loading fields of study...', [], '');
                postJson(config.routes.fieldsOfStudy, { degree_id: degree.value })
                    .then(function (data) {
                        console.log('loadFieldsOfStudy - AJAX response:', data);
                        var fields = responseItems(data.fields_of_study);
                        console.log('loadFieldsOfStudy - Fields count:', fields.length);
                        if (fields.length === 0) {
                            console.log('loadFieldsOfStudy - No fields found, loading all');
                            // If no fields of study for this degree, load all
                            postJson(config.routes.fieldsOfStudy, {})
                                .then(function (allData) {
                                    console.log('loadFieldsOfStudy - All fields response:', allData);
                                    fillSelect(fieldOfStudy, 'Choose Field / Major', responseItems(allData.fields_of_study), fieldOfStudy.dataset.selected || '');
                                    fieldOfStudy.dataset.selected = '';
                                })
                                .catch(function (error) {
                                    console.error('loadFieldsOfStudy - Error loading all fields:', error);
                                    fillSelect(fieldOfStudy, 'Unable to load fields of study', [], '');
                                });
                        } else {
                            fillSelect(fieldOfStudy, 'Choose Field / Major', fields, fieldOfStudy.dataset.selected || '');
                            fieldOfStudy.dataset.selected = '';
                        }
                    })
                    .catch(function (error) {
                        console.error('loadFieldsOfStudy - AJAX error:', error);
                        fillSelect(fieldOfStudy, 'Unable to load fields of study', [], '');
                    });
            }

            function loadInstitutions() {
                var country = flow.querySelector('[data-location-country="profile"]');
                
                console.log('loadInstitutions called - Country value:', country ? country.value : 'null');
                
                if (!institution || !config.routes) {
                    console.log('loadInstitutions - Missing institution or routes');
                    return;
                }

                var payload = {};
                if (country && country.value) payload.country_id = country.value;

                console.log('loadInstitutions - Calling AJAX with payload:', payload);
                fillSelect(institution, 'Loading institutions...', [], '');
                postJson(config.routes.institutions, payload)
                    .then(function (data) {
                        console.log('loadInstitutions - AJAX response:', data);
                        var institutions = responseItems(data.institutions);
                        console.log('loadInstitutions - Institutions count:', institutions.length);
                        if (institutions.length === 0) {
                            console.log('loadInstitutions - No institutions found, loading all');
                            // If no institutions with country filter, load all
                            postJson(config.routes.institutions, {})
                                .then(function (allData) {
                                    console.log('loadInstitutions - All institutions response:', allData);
                                    fillSelect(institution, 'Choose Institution', responseItems(allData.institutions), institution.dataset.selected || '');
                                    institution.dataset.selected = '';
                                })
                                .catch(function (error) {
                                    console.error('loadInstitutions - Error loading all institutions:', error);
                                    fillSelect(institution, 'Unable to load institutions', [], '');
                                });
                        } else {
                            fillSelect(institution, 'Choose Institution', institutions, institution.dataset.selected || '');
                            institution.dataset.selected = '';
                        }
                    })
                    .catch(function (error) {
                        console.error('loadInstitutions - AJAX error:', error);
                        fillSelect(institution, 'Unable to load institutions', [], '');
                    });
            }

            function resetFieldOfStudy() {
                if (fieldOfStudy) fillSelect(fieldOfStudy, '', [], '');
            }

            function resetInstitution() {
                if (institution) fillSelect(institution, '', [], '');
            }

            function handleEducationStatus() {
                if (!educationStatus || !expectedGradYear) return;
                
                function toggleExpectedGradYear() {
                    expectedGradYear.closest('.form-group').classList.toggle('d-none', educationStatus.value !== 'in_progress');
                    if (educationStatus.value !== 'in_progress') {
                        expectedGradYear.value = '';
                    }
                }
                
                educationStatus.addEventListener('change', toggleExpectedGradYear);
                toggleExpectedGradYear();
            }

            educationLevel.addEventListener('change', function () {
                console.log('educationLevel change event triggered');
                loadDegrees();
            });
            degree.addEventListener('change', function () {
                console.log('degree change event triggered');
                loadFieldsOfStudy();
            });
            
            // Auto-load institutions when country changes only
            var country = flow.querySelector('[data-location-country="profile"]');
            
            console.log('setupEducationDropdowns - Country element:', !!country);
            
            if (country) {
                country.addEventListener('change', function () {
                    console.log('country change event triggered');
                    loadInstitutions();
                });
            }

            handleEducationStatus();
            
            console.log('setupEducationDropdowns - Initial load check');
            if (educationLevel.value) {
                console.log('setupEducationDropdowns - educationLevel has value, loading degrees');
                loadDegrees();
            }
            if (country && country.value) {
                console.log('setupEducationDropdowns - country has value, loading institutions');
                loadInstitutions();
            }
        }

        function setupReligionSectDropdowns() {
            var religion = flow.querySelector('[data-religion="1"]');
            var sectMain = flow.querySelector('[data-sect-main="1"]');
            var schoolOfThought = flow.querySelector('[data-school-of-thought="1"]');
            var tradition = flow.querySelector('[data-tradition="1"]');
            
            if (!religion || !sectMain || !config.routes) return;

            function loadSectMain() {
                if (!religion.value) {
                    fillSelect(sectMain, '', [], '');
                    resetSchoolOfThought();
                    resetTradition();
                    return;
                }

                fillSelect(sectMain, 'Loading sects...', [], '');
                postJson(config.routes.sectMain, { religion_id: religion.value })
                    .then(function (data) {
                        fillSelect(sectMain, 'Choose Main Sect', responseItems(data.sect_mains), sectMain.dataset.selected || '');
                        sectMain.dataset.selected = '';
                        if (sectMain.value) loadSchoolOfThought();
                    })
                    .catch(function () {
                        fillSelect(sectMain, 'Unable to load sects', [], '');
                    });
            }

            function loadSchoolOfThought() {
                resetTradition();
                if (!sectMain.value) {
                    fillSelect(schoolOfThought, config.messages.selectSectMain || 'Select sect first', [], '');
                    return;
                }

                fillSelect(schoolOfThought, 'Loading schools of thought...', [], '');
                postJson(config.routes.schoolOfThought, { sect_main_id: sectMain.value })
                    .then(function (data) {
                        fillSelect(schoolOfThought, 'Choose School of Thought', responseItems(data.schools_of_thought), schoolOfThought.dataset.selected || '');
                        schoolOfThought.dataset.selected = '';
                        if (schoolOfThought.value) loadTraditions();
                    })
                    .catch(function () {
                        fillSelect(schoolOfThought, 'Unable to load schools of thought', [], '');
                    });
            }

            function loadTraditions() {
                if (!schoolOfThought.value) {
                    fillSelect(tradition, config.messages.selectSchoolOfThought || 'Select school of thought first', [], '');
                    return;
                }

                fillSelect(tradition, 'Loading traditions...', [], '');
                postJson(config.routes.traditions, { school_of_thought_id: schoolOfThought.value })
                    .then(function (data) {
                        fillSelect(tradition, 'Choose Tradition', responseItems(data.traditions), tradition.dataset.selected || '');
                        tradition.dataset.selected = '';
                    })
                    .catch(function () {
                        fillSelect(tradition, 'Unable to load traditions', [], '');
                    });
            }

            function resetSchoolOfThought() {
                if (schoolOfThought) fillSelect(schoolOfThought, '', [], '');
            }

            function resetTradition() {
                if (tradition) fillSelect(tradition, '', [], '');
            }

            religion.addEventListener('change', function () {
                loadSectMain();
            });
            sectMain.addEventListener('change', function () {
                loadSchoolOfThought();
            });
            schoolOfThought.addEventListener('change', function () {
                loadTraditions();
            });
            
            if (religion.value) loadSectMain();
        }

        function setupPhoneCountryCode() {
            var phone = document.getElementById('phone-code');
            var countryCode = form ? form.querySelector('input[name="country_code"]') : null;
            if (!phone || !countryCode) return;

            function setCode(value) {
                countryCode.value = String(value || countryCode.value || '92').replace(/^\+/, '');
            }

            if (window.intlTelInput) {
                var iti = window.intlTelInput(phone, {
                    initialCountry: 'pk',
                    preferredCountries: ['pk', 'sa', 'ae', 'gb', 'us'],
                    separateDialCode: true,
                    nationalMode: true,
                    dropdownContainer: document.body
                });
                var sync = function () {
                    var data = iti.getSelectedCountryData();
                    setCode(data && data.dialCode ? data.dialCode : '92');
                };
                phone.addEventListener('countrychange', sync);
                sync();
            } else {
                setCode(countryCode.value || '92');
            }
        }

        function splitFullName() {
            var fullName = document.getElementById('registration_full_name');
            var firstName = document.getElementById('first_name');
            var lastName = document.getElementById('last_name');
            if (!fullName || !firstName || !lastName) return;

            var parts = fullName.value.trim().split(/\s+/).filter(Boolean);
            var first = parts.shift() || '';
            var last = parts.join(' ') || first;
            firstName.value = first;
            lastName.value = last;
        }

        function syncLanguage() {
            var motherTongue = document.getElementById('registration_mother_tongue');
            var knownLanguage = document.getElementById('registration_known_language');
            if (!motherTongue || !knownLanguage) return;
            knownLanguage.innerHTML = '<option value="' + motherTongue.value + '" selected></option>';
        }

        function combineDateFields() {
            var day = document.getElementById('dob_day');
            var month = document.getElementById('dob_month');
            var year = document.getElementById('dob_year');
            var hidden = document.getElementById('date_of_birth');
            if (!day || !month || !year || !hidden) return;
            if (day.value && month.value && year.value) {
                hidden.value = year.value + '-' + month.value + '-' + day.value;
            } else {
                hidden.value = '';
            }
        }

        function toggleConditionalFields() {
            var gender = document.getElementById('gender');
            var femaleWork = flow.querySelector('[data-female-work-field]');
            if (femaleWork && gender) {
                femaleWork.classList.toggle('d-none', String(gender.value) !== '2');
            }
        }

        function validateCurrentStep() {
            var step = flow.querySelector('[data-registration-step="' + current + '"]');
            if (!step || step.dataset.skippable === '1') return true;
            if (current === 2) splitFullName();
            if (current === 3) syncLanguage();

            var fields = Array.from(step.querySelectorAll('input, select, textarea'));
            for (var i = 0; i < fields.length; i++) {
                var field = fields[i];
                if (field.disabled || !field.required || field.type === 'hidden') continue;

                if (field.type === 'file') {
                    if (!field.files || field.files.length === 0) {
                        field.classList.add('is-invalid');
                        field.focus();
                        notify(config.messages.required);
                        return false;
                    }
                    continue;
                }

                if (!String(field.value || '').trim()) {
                    field.classList.add('is-invalid');
                    field.focus();
                    notify(config.messages.required);
                    return false;
                }

                field.classList.remove('is-invalid');
            }

            if (current === 11) {
                var profilePhoto = form.querySelector('[name="profile_photo"]');
                if (profilePhoto && profilePhoto.files && profilePhoto.files.length === 0) {
                    profilePhoto.classList.add('is-invalid');
                    profilePhoto.focus();
                    notify(config.messages.required);
                    return false;
                }
            }

            if (current === 17) {
                var minAge = Number(form.querySelector('[name="partner_age_min"]').value || 0);
                var maxAge = Number(form.querySelector('[name="partner_age_max"]').value || 0);
                if (minAge > maxAge) {
                    notify(config.messages.ageRange);
                    return false;
                }
                
                var minIncome = Number(form.querySelector('[name="partner_income_min"]').value || 0);
                var maxIncome = Number(form.querySelector('[name="partner_income_max"]').value || 0);
                if (minIncome > maxIncome) {
                    notify('Partner income minimum must be less than or equal to maximum.');
                    return false;
                }
            }

            if (current === 11) {
                var profilePhoto = form.querySelector('[name="profile_photo"]');
                // Make profile photo optional for testing
                /*if (profilePhoto && profilePhoto.files && profilePhoto.files.length === 0) {
                    profilePhoto.classList.add('is-invalid');
                    profilePhoto.focus();
                    notify(config.messages.required);
                    return false;
                }*/
                
                var additionalPhotos = form.querySelector('[name="additional_photos[]"]');
                console.log('Validating additional photos - element:', !!additionalPhotos, 'files:', additionalPhotos ? additionalPhotos.files : 'no files');
                // Make additional photos optional for testing
                /*if (additionalPhotos && additionalPhotos.files && additionalPhotos.files.length < 2) {
                    console.log('Additional photos validation failed - only', additionalPhotos.files.length, 'files uploaded');
                    additionalPhotos.classList.add('is-invalid');
                    additionalPhotos.focus();
                    notify('Please upload at least 2 additional photos.');
                    return false;
                }*/
            }

            if (current === 18) {
                var password = form.querySelector('[name="password"]');
                var confirm = form.querySelector('[name="password_confirmation"]');
                if (password && confirm && password.value !== confirm.value) {
                    confirm.classList.add('is-invalid');
                    notify(config.messages.passwordMismatch);
                    return false;
                }
            }

            return true;
        }

        function renderStepper() {
            var start = Math.floor((current - 1) / 3) * 3 + 1;
            flow.querySelectorAll('[data-step-slot]').forEach(function (slot, index) {
                var stepNumber = start + index;
                var step = steps[stepNumber - 1];
                slot.classList.toggle('d-none', stepNumber > total);
                slot.classList.toggle('is-active', stepNumber === current);
                slot.classList.toggle('is-complete', stepNumber < current);
                slot.querySelector('[data-step-slot-number]').textContent = stepNumber;
                var label = slot.querySelector('[data-step-slot-label]');
                if (label) label.textContent = '';
            });
        }

        function syncEmailFields() {
            var emailInput = document.getElementById('signinSrEmail');
            var emailVerifyInput = document.getElementById('signinSrEmailVerify');
            if (!emailInput || !emailVerifyInput) return;
            emailVerifyInput.value = emailInput.value;
        }

        function setupEmailSync() {
            var emailInput = document.getElementById('signinSrEmail');
            var emailVerifyInput = document.getElementById('signinSrEmailVerify');
            if (!emailInput || !emailVerifyInput) return;
            // Push changes from step 11's email back to step 5's email
            emailVerifyInput.addEventListener('input', function () {
                emailInput.value = emailVerifyInput.value;
            });
        }


        function flattenErrors(errors) {
            var messages = [];
            if (!errors) return messages;
            Object.keys(errors).forEach(function (key) {
                var value = errors[key];
                if (Array.isArray(value)) {
                    value.forEach(function (message) { messages.push(message); });
                } else if (value) {
                    messages.push(String(value));
                }
            });
            return messages;
        }

        function markInvalidFields(errors) {
            if (!errors || !form) return;
            Object.keys(errors).forEach(function (key) {
                var field = form.querySelector('[name="' + key + '"]') || form.querySelector('[name="' + key + '[]"]');
                if (field) field.classList.add('is-invalid');
            });
        }

        function setSubmitting(state) {
            isSubmitting = state;
            if (submit) {
                if (!submit.dataset.defaultText) {
                    submit.dataset.defaultText = submit.innerHTML;
                }
                submit.disabled = state;
                submit.innerHTML = state ? 'Creating Account...' : submit.dataset.defaultText;
            }
            if (prev) prev.disabled = state || current === 1;
            if (next) next.disabled = state;
        }

        function submitRegistrationAjax() {
            console.log('submitRegistrationAjax called');
            if (!form || isSubmitting) {
                console.log('submitRegistrationAjax - Form not found or already submitting');
                return;
            }

            splitFullName();
            syncLanguage();
            combineDateFields();
            syncEmailFields();

            var currentStepInput = form.querySelector('input[name="current_step"]');
            if (!currentStepInput) {
                currentStepInput = document.createElement('input');
                currentStepInput.type = 'hidden';
                currentStepInput.name = 'current_step';
                form.appendChild(currentStepInput);
            }
            currentStepInput.value = String(current);

            var formData = new FormData(form);
            console.log('submitRegistrationAjax - Form action:', form.action);
            console.log('submitRegistrationAjax - Current step:', current);
            console.log('submitRegistrationAjax - FormData entries count:', Array.from(formData.entries()).length);
            
            setSubmitting(true);

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData,
                credentials: 'same-origin'
            })
                .then(function (response) {
                    console.log('submitRegistrationAjax - Response status:', response.status, response.statusText);
                    return response.text().then(function (text) {
                        console.log('submitRegistrationAjax - Response text:', text);
                        var data = {};
                        try {
                            data = text ? JSON.parse(text) : {};
                        } catch (error) {
                            console.error('submitRegistrationAjax - JSON parse error:', error);
                            data = { success: false, message: 'Unexpected server response.', raw: text };
                        }
                        return { ok: response.ok, status: response.status, data: data };
                    });
                })
                .then(function (result) {
                    console.log('submitRegistrationAjax - Processed result:', result);
                    
                    // Check for success in multiple possible response formats
                    var isSuccess = false;
                    var redirectUrl = '/member/dashboard';
                    var successMessage = 'Registration successful!';
                    
                    if (result.ok) {
                        if (result.data && result.data.success) {
                            isSuccess = true;
                            redirectUrl = result.data.redirect || '/member/dashboard';
                            successMessage = result.data.message || 'Registration successful!';
                        } else if (result.data && (result.data.status === 'success' || result.data.status === 200)) {
                            isSuccess = true;
                            redirectUrl = result.data.redirect || result.data.url || '/member/dashboard';
                            successMessage = result.data.message || 'Registration successful!';
                        } else if (result.status === 200 || result.status === 201) {
                            isSuccess = true;
                            redirectUrl = result.data?.redirect || result.data?.url || '/member/dashboard';
                            successMessage = result.data?.message || 'Registration successful!';
                        }
                    }
                    
                    console.log('submitRegistrationAjax - Success check:', isSuccess, 'Redirect URL:', redirectUrl);
                    
                    if (isSuccess) {
                        console.log('submitRegistrationAjax - Registration successful, showing success message then redirecting');
                        notify(successMessage, 'success');
                        
                        // Show success message for 2 seconds before redirect
                        setTimeout(function() {
                            console.log('submitRegistrationAjax - Redirecting to:', redirectUrl);
                            window.location.href = redirectUrl;
                        }, 2000);
                        return;
                    } else {
                        console.error('submitRegistrationAjax - Registration failed, result:', result);
                        markInvalidFields(result.data.errors || {});
                        var messages = flattenErrors(result.data.errors || {});
                        notify(messages[0] || result.data?.message || 'Registration failed. Please try again.');
                    }
                })
                .catch(function (error) {
                    console.error('submitRegistrationAjax - AJAX exception:', error);
                    notify('Registration request failed. Please try again.');
                })
                .finally(function () {
                    setSubmitting(false);
                    showStep(current);
                });
        }
        function showStep(stepNumber) {
            current = Math.max(1, Math.min(total, stepNumber));
            steps.forEach(function (step) {
                step.classList.toggle('d-none', Number(step.dataset.registrationStep) !== current);
            });

            var active = steps[current - 1];
            if (heading && active) heading.textContent = 'Step ' + current + ': ' + active.dataset.stepTitle;
            if (subheading && active) subheading.textContent = active.dataset.stepSubtitle || '';
            if (prev) prev.disabled = current === 1;
            if (next) next.classList.toggle('d-none', current === total);
            if (submit) submit.classList.toggle('d-none', current !== total);
            if (terms) terms.classList.toggle('d-none', current !== total);

            renderStepper();
            refreshSelects();
            toggleConditionalFields();
            if (current === 18) syncEmailFields();
            window.scrollTo({ top: flow.getBoundingClientRect().top + window.pageYOffset - 90, behavior: 'smooth' });
        }

        if (prev) prev.addEventListener('click', function () { showStep(current - 1); });
        if (next) next.addEventListener('click', function () { if (validateCurrentStep()) showStep(current + 1); });

        form.addEventListener('submit', function (event) {
            console.log('Form submit event triggered, current step:', current, 'total steps:', total);
            event.preventDefault(); // Always prevent default to use AJAX
            event.stopPropagation();
            
            splitFullName();
            syncLanguage();
            
            if (current !== total || !validateCurrentStep()) {
                console.log('Form submit - not final step or validation failed, showing current step');
                showStep(current);
                return;
            }
            
            console.log('Form submit - final step with validation passed, calling submitRegistrationAjax');
            submitRegistrationAjax();
        });

        flow.addEventListener('change', function (event) {
            if (event.target) event.target.classList.remove('is-invalid');
        });
        flow.addEventListener('keyup', function (event) {
            if (event.target) event.target.classList.remove('is-invalid');
        });

        var motherTongue = document.getElementById('registration_mother_tongue');
        if (motherTongue) motherTongue.addEventListener('change', syncLanguage);

        // Date of birth select combiner
        ['dob_day', 'dob_month', 'dob_year'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.addEventListener('change', combineDateFields);
        });
        combineDateFields();

        var gender = document.getElementById('gender');
        if (gender) gender.addEventListener('change', toggleConditionalFields);

        var about = flow.querySelector('[name="about_me"]');
        var aboutCounter = flow.querySelector('[data-about-counter]');
        if (about && aboutCounter) {
            var updateCounter = function () { aboutCounter.textContent = about.value.length; };
            about.addEventListener('input', updateCounter);
            updateCounter();
        }

        setupDropdownStacking();
        setupPhoneCountryCode();
        setupLocation('profile');
        setupLocation('partner');
        setupCaste();
        loadInstitutionsForSelectedCountry();
        syncLanguage();
        setupEmailSync();
        
        // New controlled dropdowns
        setupProfessionDropdowns();
        setupEducationDropdowns();
        setupReligionSectDropdowns();
        
        showStep(1);
    });
})();
