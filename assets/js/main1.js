document.addEventListener("DOMContentLoaded", () => {
    const loginForm = document.getElementById("loginForm");
    const loginMessage = document.getElementById("loginMessage");
    const loginBtn = document.getElementById("loginBtn");
    const logoutButtons = document.querySelectorAll("[data-logout]");
    const jsonForms = document.querySelectorAll("[data-json-form]");
    const instantToggles = document.querySelectorAll("[data-instant-toggle]");
    const searchInputs = document.querySelectorAll("[data-search-input]");
    const invoiceForm = document.querySelector("[data-invoice-form]");
    const fiscalizeButtons = document.querySelectorAll("[data-fiscalize-invoice]");

    const readJsonScript = (id) => {
        const element = document.getElementById(id);
        if (!element) {
            return [];
        }

        try {
            return JSON.parse(element.textContent || "[]");
        } catch (error) {
            console.error(`Failed to parse JSON from ${id}`, error);
            return [];
        }
    };

    const isValidOib = (value) => {
        const oib = String(value || "").replace(/\D+/g, "");

        if (!/^\d{11}$/.test(oib)) {
            return false;
        }

        let control = 10;

        for (let i = 0; i < 10; i += 1) {
            control = (control + Number(oib[i])) % 10;

            if (control === 0) {
                control = 10;
            }

            control = (control * 2) % 11;
        }

        let checkDigit = 11 - control;

        if (checkDigit === 10 || checkDigit === 11) {
            checkDigit = 0;
        }

        return checkDigit === Number(oib[10]);
    };

    if (loginForm) {
        loginForm.addEventListener("submit", async (e) => {
            e.preventDefault();

        loginMessage.textContent = "";
        loginMessage.className = "form-message";

        const email = document.getElementById("email").value.trim();
        const password = document.getElementById("password").value.trim();

        if (!email || !password) {
            loginMessage.textContent = "Email i lozinka su obavezni.";
            loginMessage.classList.add("error");
            return;
        }

        loginBtn.disabled = true;
        loginBtn.textContent = "Prijava u tijeku...";

        try {
            const response = await fetch("api/login.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    email: email,
                    password: password
                })
            });

            const result = await response.json();

            if (result.success) {
                loginMessage.textContent = result.message;
                loginMessage.classList.add("success");

                setTimeout(() => {
                    window.location.href = result.redirect;
                }, 800);
            } else {
                loginMessage.textContent = result.message || "Prijava nije uspjela.";
                loginMessage.classList.add("error");
            }

        } catch (error) {
            loginMessage.textContent = "Greška pri komunikaciji sa serverom.";
            loginMessage.classList.add("error");
            console.error("Login error:", error);
        } finally {
            loginBtn.disabled = false;
            loginBtn.textContent = "Prijava";
        }
        });
    }

    logoutButtons.forEach((button) => {
        button.addEventListener("click", async (event) => {
            event.preventDefault();
            event.stopPropagation();
            button.disabled = true;

            try {
                const logoutUrl = button.dataset.logoutUrl || "../api/logout.php";
                const response = await fetch(logoutUrl, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({})
                });

                const result = await response.json();

                if (result.success && result.redirect) {
                    window.location.replace(result.redirect);
                    return;
                }

                alert(result.message || "Odjava nije uspjela.");
            } catch (error) {
                console.error("Logout error:", error);
                alert("Greska pri komunikaciji sa serverom.");
            } finally {
                button.disabled = false;
            }
        });
    });

    jsonForms.forEach((form) => {
        form.addEventListener("submit", async (event) => {
            event.preventDefault();

            const endpoint = form.dataset.endpoint;
            const submitButton = form.querySelector('[type="submit"]');
            const formMessage = form.querySelector("[data-form-message]");
            const formData = new FormData(form);
            const payload = {};

            form.querySelectorAll('input[type="checkbox"]').forEach((input) => {
                payload[input.name] = input.checked ? 1 : 0;
            });

            formData.forEach((value, key) => {
                if (payload[key] === undefined) {
                    payload[key] = typeof value === "string" ? value.trim() : value;
                }
            });

            if (formMessage) {
                formMessage.className = "alert d-none";
                formMessage.textContent = "";
            }

            if (submitButton) {
                submitButton.disabled = true;
            }

            try {
                const response = await fetch(endpoint, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({ data: payload })
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || "Spremanje nije uspjelo.");
                }

                if (formMessage) {
                    formMessage.className = "alert alert-success";
                    formMessage.textContent = result.message || form.dataset.successMessage || "Podaci su spremljeni.";
                }

                setTimeout(() => {
                    window.location.reload();
                }, 700);
            } catch (error) {
                if (formMessage) {
                    formMessage.className = "alert alert-danger";
                    formMessage.textContent = error.message || "Greska pri spremanju podataka.";
                }
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                }
            }
        });
    });

    instantToggles.forEach((toggle) => {
        toggle.addEventListener("change", async () => {
            const endpoint = toggle.dataset.endpoint;
            const field = toggle.dataset.field;
            const messageBox = document.getElementById("bunitOptionsMessage") || document.getElementById("companyOptionsMessage");
            const previousValue = !toggle.checked;

            toggle.disabled = true;

            if (messageBox) {
                messageBox.className = "alert d-none mb-0";
                messageBox.textContent = "";
            }

            try {
                const response = await fetch(endpoint, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        field,
                        value: toggle.checked ? 1 : 0
                    })
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || "Spremanje nije uspjelo.");
                }

                if (messageBox) {
                    messageBox.className = "alert alert-success mb-0";
                    messageBox.textContent = result.message || "Opcija je spremljena.";
                }
            } catch (error) {
                toggle.checked = previousValue;

                if (messageBox) {
                    messageBox.className = "alert alert-danger mb-0";
                    messageBox.textContent = error.message || "Greska pri spremanju opcije.";
                }
            } finally {
                toggle.disabled = false;
            }
        });
    });

    searchInputs.forEach((input) => {
        input.addEventListener("input", () => {
            const targetSelector = input.dataset.searchTarget;
            const tableBody = targetSelector ? document.querySelector(targetSelector) : null;

            if (!tableBody) {
                return;
            }

            const term = input.value.trim().toLowerCase();
            const rows = tableBody.querySelectorAll("[data-search-row]");

            rows.forEach((row) => {
                const matches = row.textContent.toLowerCase().includes(term);
                row.style.display = matches ? "" : "none";
            });
        });
    });

    fiscalizeButtons.forEach((button) => {
        button.addEventListener("click", async () => {
            const endpoint = button.dataset.endpoint;
            const invoiceId = Number(button.dataset.invoiceId || 0);

            if (!endpoint || !invoiceId) {
                return;
            }

            button.disabled = true;

            try {
                const response = await fetch(endpoint, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({ invoice_id: invoiceId })
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || "Fiscalization request generation failed.");
                }

                window.location.reload();
            } catch (error) {
                alert(error.message || "Fiscalization request generation failed.");
            } finally {
                button.disabled = false;
            }
        });
    });

    if (invoiceForm) {
        const invoiceCustomers = readJsonScript("invoiceCustomersData");
        const invoiceArticles = readJsonScript("invoiceArticlesData");
        const invoicePrefill = typeof window.invoicePrefillData !== "undefined"
            ? window.invoicePrefillData
            : (document.getElementById("invoicePrefillData") ? readJsonScript("invoicePrefillData") : null);
        const invoicePayment = invoiceForm.querySelector("#invoice-payment");
        const invoiceUpdateEndpoint = invoiceForm.dataset.updateEndpoint || "";
        const invoiceMessage = document.getElementById("invoiceFormMessage");
        const invoicePrimaryButton = invoiceForm.querySelector("#invoicePrimaryButton") || invoiceForm.querySelector('[type="submit"]');
        const saveInvoiceChangesButton = invoiceForm.querySelector("#saveInvoiceChangesButton");
        const invoiceNumberLabel = invoiceForm.querySelector("[data-invoice-number-label]");
        const invoiceNumberPreview = invoiceForm.querySelector("#invoice-number-preview");
        const customerFullName = invoiceForm.querySelector("#invoice-customer-full-name");
        const customerOib = invoiceForm.querySelector("#invoice-customer-oib");
        const customerLegal = invoiceForm.querySelector("#invoice-customer-legal");
        const customerAddress = invoiceForm.querySelector("#invoice-customer-address");
        const customerCity = invoiceForm.querySelector("#invoice-customer-city");
        const customerCountry = invoiceForm.querySelector("#invoice-customer-country");
        const customerEmail = invoiceForm.querySelector("#invoice-customer-email");
        const invoiceRemark = invoiceForm.querySelector("#invoice-ending-note");
        const invoiceDate = invoiceForm.querySelector("#invoice-date");
        const invoiceDueDate = invoiceForm.querySelector("#invoice-due-date");
        const invoiceDateHeading = invoiceForm.querySelector("[data-invoice-date-heading]");
        const invoiceDueDateLabel = invoiceForm.querySelector("[data-invoice-due-date-label]");
        const invoiceCustomerTypeCode = invoiceForm.querySelector("[data-invoice-customer-type-code]");
        const articleSearch = invoiceForm.querySelector("#invoice-article-search");
        const articleSuggestions = invoiceForm.querySelector("#invoice-article-suggestions");
        const addInvoiceArticleButton = invoiceForm.querySelector("#addInvoiceArticleButton");
        const invoiceSecondaryAddArticleButton = invoiceForm.querySelector("#invoiceSecondaryAddArticleButton");
        const invoiceArticlesTableBody = invoiceForm.querySelector("#invoiceArticlesTableBody");
        const invoiceArticleRowTemplate = document.getElementById("invoiceArticleRowTemplate");
        const invoiceItemCount = invoiceForm.querySelector("[data-invoice-item-count]");
        const invoiceTotalBase = invoiceForm.querySelector("[data-invoice-total-base]");
        const invoiceTotalVat = invoiceForm.querySelector("[data-invoice-total-vat]");
        const invoiceGrandTotal = invoiceForm.querySelector("[data-invoice-grand-total]");
        let currentInvoiceId = null;
        let nextInvoiceNumber = invoiceNumberLabel ? parseInt(invoiceNumberLabel.textContent || "1", 10) || 1 : 1;
        let articleSuggestionIndex = -1;

        const formatDateLabel = (value) => {
            if (!value) {
                return "";
            }

            const parts = value.split("-");
            if (parts.length !== 3) {
                return value;
            }

            return `${parts[2]}.${parts[1]}.${parts[0]}`;
        };

        const syncInvoiceDates = () => {
            if (invoiceDateHeading && invoiceDate) {
                invoiceDateHeading.textContent = formatDateLabel(invoiceDate.value);
            }
            if (invoiceDueDateLabel && invoiceDueDate) {
                invoiceDueDateLabel.textContent = formatDateLabel(invoiceDueDate.value);
            }
        };

        const syncInvoiceCustomerTypeCode = () => {
            if (!invoiceCustomerTypeCode) {
                return;
            }

            invoiceCustomerTypeCode.textContent = customerLegal && customerLegal.checked ? "F1" : "F2";
        };

        const setInvoiceTitle = (invoiceNumber, isDraft) => {
            const title = invoiceForm.querySelector(".invoice-bar__title");
            if (!title) {
                return;
            }

            title.innerHTML = isDraft
                ? `Invoice No. <span data-invoice-number-label>${invoiceNumber}</span> - Draft`
                : `Invoice No. <span data-invoice-number-label>${invoiceNumber}</span>`;
        };

        const setSavedInvoiceState = (invoiceId, invoiceNumber, upcomingNumber) => {
            currentInvoiceId = invoiceId;
            nextInvoiceNumber = upcomingNumber || (invoiceNumber + 1);
            setInvoiceTitle(invoiceNumber, false);
            if (invoiceNumberPreview) {
                invoiceNumberPreview.value = String(invoiceNumber);
            }
            if (invoicePrimaryButton) {
                invoicePrimaryButton.textContent = "Novi račun";
                invoicePrimaryButton.type = "button";
            }
            if (saveInvoiceChangesButton) {
                saveInvoiceChangesButton.classList.remove("d-none");
            }
        };

        const resetInvoiceFormForNewInvoice = () => {
            currentInvoiceId = null;
            invoiceForm.reset();
            invoiceArticlesTableBody.innerHTML = "";
            setInvoiceTitle(nextInvoiceNumber, true);
            if (invoiceNumberPreview) {
                invoiceNumberPreview.value = String(nextInvoiceNumber);
            }
            if (invoicePrimaryButton) {
                invoicePrimaryButton.textContent = "Izdaj";
                invoicePrimaryButton.type = "submit";
            }
            if (saveInvoiceChangesButton) {
                saveInvoiceChangesButton.classList.add("d-none");
            }
            syncInvoiceDates();
            syncInvoiceCustomerTypeCode();
            refreshInvoiceSummary();
            if (invoiceMessage) {
                invoiceMessage.className = "alert d-none";
                invoiceMessage.textContent = "";
            }
            if (articleSearch) {
                articleSearch.value = "";
            }
        };

        const collectInvoicePayload = () => {
            const selectedArticles = [];
            const currentArticleRows = invoiceForm.querySelectorAll("[data-invoice-article-row]");

            currentArticleRows.forEach((row) => {
                selectedArticles.push({
                    id: Number(row.dataset.articleId || 0),
                    amount: parseInt(row.querySelector("[data-article-amount]").value || "0", 10),
                    unit: row.querySelector("[data-article-unit]").value,
                    discount: parseInt(row.querySelector("[data-article-discount]").value || "0", 10),
                    tip_price: parseInt(row.querySelector("[data-article-tip]").value || "0", 10),
                });
            });

            return {
                payment: invoicePayment ? invoicePayment.value : "cash_payment",
                invoice_date: invoiceDate ? invoiceDate.value : "",
                due_date: invoiceDueDate ? invoiceDueDate.value : "",
                remark: invoiceRemark ? invoiceRemark.value.trim() : "",
                customer: {
                    full_name: customerFullName.value.trim(),
                    legal: customerLegal && customerLegal.checked ? 1 : 0,
                    address: customerAddress.value.trim(),
                    city: customerCity.value.trim(),
                    country: customerCountry.value.trim(),
                    oib: customerOib.value.trim(),
                    email: customerEmail.value.trim(),
                },
                articles: selectedArticles
            };
        };

        const validateInvoicePayload = (payload) => {
            if (!payload.articles.length) {
                if (invoiceMessage) {
                    invoiceMessage.className = "alert alert-danger";
                    invoiceMessage.textContent = "Select at least one article.";
                }
                return false;
            }

            if (payload.customer.oib && !isValidOib(payload.customer.oib)) {
                if (invoiceMessage) {
                    invoiceMessage.className = "alert alert-danger";
                    invoiceMessage.textContent = "Customer OIB is not formally valid.";
                }
                return false;
            }

            return true;
        };

        const submitInvoiceRequest = async (endpoint, payload, mode) => {
            const buttonToDisable = mode === "update" ? saveInvoiceChangesButton : invoicePrimaryButton;

            if (buttonToDisable) {
                buttonToDisable.disabled = true;
            }

            try {
                const response = await fetch(endpoint, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.message || (mode === "update" ? "Updating invoice failed." : "Creating invoice failed."));
                }

                if (invoiceMessage) {
                    invoiceMessage.className = "alert alert-success";
                    invoiceMessage.textContent = result.message || (mode === "update" ? "Invoice updated successfully." : "Invoice created successfully.");
                }
                if (invoiceMessage) {
                    setTimeout(() => {
                        if (invoiceMessage.classList.contains("alert-success")) {
                            invoiceMessage.className = "alert d-none";
                            invoiceMessage.textContent = "";
                        }
                    }, 3000);
                }

                if (mode === "create") {
                    setSavedInvoiceState(result.invoice_id, Number(result.invoice_number || 0), Number(result.next_invoice_number || 0));
                } else if (result.invoice_number) {
                    setInvoiceTitle(Number(result.invoice_number), false);
                    if (invoiceNumberPreview) {
                        invoiceNumberPreview.value = String(result.invoice_number);
                    }
                }
            } catch (error) {
                if (invoiceMessage) {
                    invoiceMessage.className = "alert alert-danger";
                    invoiceMessage.textContent = error.message || (mode === "update" ? "Updating invoice failed." : "Creating invoice failed.");
                }
            } finally {
                if (buttonToDisable) {
                    buttonToDisable.disabled = false;
                }
            }
        };

        const findCustomer = (searchValue, field) => {
            const term = (searchValue || "").trim().toLowerCase();
            if (!term) {
                return null;
            }

            return invoiceCustomers.find((customer) => {
                const candidate = String(customer[field] || "").trim().toLowerCase();
                return candidate !== "" && candidate === term;
            }) || null;
        };

        const fillCustomerFields = (customer) => {
            if (!customer) {
                return;
            }

            customerFullName.value = customer.full_name || "";
            customerOib.value = customer.oib || "";
            if (customerLegal) {
                customerLegal.checked = String(customer.legal || "0") === "1";
            }
            customerAddress.value = customer.address || "";
            customerCity.value = customer.city || "";
            customerCountry.value = customer.country || "";
            customerEmail.value = customer.email || "";
            syncInvoiceCustomerTypeCode();
        };

        const isCashPayment = () => invoicePayment && invoicePayment.value === "cash_payment";

        const refreshInvoiceSummary = () => {
            const rows = invoiceForm.querySelectorAll("[data-invoice-article-row]");
            let itemCount = 0;
            let totalBase = 0;
            let totalVatValue = 0;
            let grandTotal = 0;

            rows.forEach((row, index) => {
                const amount = parseInt(row.querySelector("[data-article-amount]").value || "0", 10) || 0;
                const retailPrice = parseFloat(row.querySelector("[data-article-retail-price]").textContent || "0") || 0;
                const discount = parseInt(row.querySelector("[data-article-discount]").value || "0", 10) || 0;
                const vatRateText = row.querySelector("[data-article-vat-rate]").textContent || "0";
                const vatRate = parseFloat(vatRateText.replace("%", "")) || 0;
                const tip = isCashPayment() ? (parseInt(row.querySelector("[data-article-tip]").value || "0", 10) || 0) : 0;
                const basePrice = retailPrice * amount;
                const discountAmount = basePrice * (discount / 100);
                const discountedBase = basePrice - discountAmount;
                const vatAmount = discountedBase * (vatRate / 100);
                const rowTotal = discountedBase + vatAmount + tip;

                itemCount += 1;
                totalBase += discountedBase;
                totalVatValue += vatAmount;
                grandTotal += rowTotal;

                const indexCell = row.querySelector("[data-article-index]");
                if (indexCell) {
                    indexCell.textContent = String(index + 1);
                }
            });

            if (invoiceItemCount) {
                invoiceItemCount.textContent = String(itemCount);
            }
            if (invoiceTotalBase) {
                invoiceTotalBase.textContent = `${totalBase.toFixed(2)} EUR`;
            }
            if (invoiceTotalVat) {
                invoiceTotalVat.textContent = `${totalVatValue.toFixed(2)} EUR`;
            }
            if (invoiceGrandTotal) {
                invoiceGrandTotal.textContent = `${grandTotal.toFixed(2)} EUR`;
            }
        };

        const updateArticleRow = (row) => {
            const amountInput = row.querySelector("[data-article-amount]");
            const unitInput = row.querySelector("[data-article-unit]");
            const discountInput = row.querySelector("[data-article-discount]");
            const tipInput = row.querySelector("[data-article-tip]");
            const retailPrice = parseFloat(row.querySelector("[data-article-retail-price]").textContent || "0");
            const vatRateText = row.querySelector("[data-article-vat-rate]").textContent || "0";
            const vatRate = parseFloat(vatRateText.replace("%", "")) || 0;
                const amount = parseInt(amountInput.value || "0", 10) || 0;
                const discount = parseInt(discountInput.value || "0", 10) || 0;
                const tip = isCashPayment() ? (parseInt(tipInput.value || "0", 10) || 0) : 0;

            amountInput.value = String(amount === 0 ? 1 : amount);
            discountInput.value = String(Math.max(0, Math.min(discount, 100)));
            tipInput.disabled = !isCashPayment();

            if (!isCashPayment()) {
                tipInput.value = "0";
            }

            let finalPrice = 0;
            if (amount !== 0) {
                const basePrice = retailPrice * amount;
                const discountAmount = basePrice * ((parseInt(discountInput.value || "0", 10) || 0) / 100);
                const priceAfterDiscount = basePrice - discountAmount;
                const vatAmount = priceAfterDiscount * (vatRate / 100);
                finalPrice = priceAfterDiscount + vatAmount + tip;
            }

            row.querySelector("[data-article-final-price]").textContent = finalPrice.toFixed(2);
            unitInput.value = unitInput.value || "";
            refreshInvoiceSummary();
        };

        const bindArticleRow = (row) => {
            row.querySelector("[data-article-amount]").addEventListener("input", () => updateArticleRow(row));
            row.querySelector("[data-article-discount]").addEventListener("input", () => updateArticleRow(row));
            row.querySelector("[data-article-tip]").addEventListener("input", () => updateArticleRow(row));
            row.querySelector("[data-remove-article]").addEventListener("click", () => {
                row.remove();
                refreshInvoiceSummary();
            });
            updateArticleRow(row);
        };

        const addArticleToTable = (article, presetValues = null) => {
            if (!invoiceArticlesTableBody || !invoiceArticleRowTemplate || !article) {
                return;
            }

            if (invoiceArticlesTableBody.querySelector(`[data-invoice-article-row][data-article-id="${article.id}"]`)) {
                return;
            }

            const fragment = invoiceArticleRowTemplate.content.cloneNode(true);
            const row = fragment.querySelector("[data-invoice-article-row]");
            row.dataset.articleId = String(article.id || "");
            row.querySelector("[data-article-label]").textContent = article.label || "";
            row.querySelector("[data-article-retail-price]").textContent = Number(article.retail_price || 0).toFixed(2);
            row.querySelector("[data-article-vat-rate]").textContent = `${article.vat_rate || 0}%`;
            row.querySelector("[data-article-unit-label]").textContent = article.unit || "";
            row.querySelector("[data-article-unit]").value = article.unit || "";
            invoiceArticlesTableBody.appendChild(fragment);
            const appendedRow = invoiceArticlesTableBody.lastElementChild;

            if (presetValues) {
                if (typeof presetValues.amount !== "undefined") {
                    appendedRow.querySelector("[data-article-amount]").value = String(parseInt(presetValues.amount, 10) || 0);
                }
                if (typeof presetValues.discount !== "undefined") {
                    appendedRow.querySelector("[data-article-discount]").value = String(parseInt(presetValues.discount, 10) || 0);
                }
                if (typeof presetValues.tip_price !== "undefined") {
                    appendedRow.querySelector("[data-article-tip]").value = String(parseInt(presetValues.tip_price, 10) || 0);
                }
            }

            bindArticleRow(appendedRow);
        };

        if (invoicePayment) {
            invoicePayment.addEventListener("change", () => {
                invoiceForm.querySelectorAll("[data-invoice-article-row]").forEach((row) => updateArticleRow(row));
                refreshInvoiceSummary();
            });
        }

        invoiceDate?.addEventListener("change", syncInvoiceDates);
        invoiceDueDate?.addEventListener("change", syncInvoiceDates);
        customerLegal?.addEventListener("change", syncInvoiceCustomerTypeCode);

        customerFullName?.addEventListener("change", () => {
            fillCustomerFields(findCustomer(customerFullName.value, "full_name"));
        });

        customerOib?.addEventListener("change", () => {
            fillCustomerFields(findCustomer(customerOib.value, "oib"));
        });

        const findArticle = (searchValue) => {
            const term = (searchValue || "").trim().toLowerCase();
            if (!term) {
                return null;
            }

            return invoiceArticles.find((article) => {
                const label = String(article.label || "").trim().toLowerCase();
                return label === term;
            }) || null;
        };

        const hideArticleSuggestions = () => {
            if (articleSuggestions) {
                articleSuggestions.classList.add("d-none");
                articleSuggestions.innerHTML = "";
            }
            articleSuggestionIndex = -1;
        };

        const clearArticleSearch = () => {
            if (!articleSearch) {
                return;
            }

            articleSearch.blur();
            articleSearch.value = "";
            articleSearch.setAttribute("value", "");
            // Run again after the click cycle so browser focus handling cannot restore the old value.
            requestAnimationFrame(() => {
                articleSearch.value = "";
            });
            setTimeout(() => {
                articleSearch.value = "";
                articleSearch.setAttribute("value", "");
            }, 0);
        };

        const showArticleSuggestions = (searchValue) => {
            if (!articleSuggestions) {
                return;
            }

            const term = (searchValue || "").trim().toLowerCase();
            const matches = invoiceArticles
                .filter((article) => {
                    if (!term) {
                        return true;
                    }

                    return String(article.label || "").toLowerCase().includes(term);
                })
                .slice(0, 8);

            if (!matches.length) {
                hideArticleSuggestions();
                return;
            }

            articleSuggestions.innerHTML = "";
            articleSuggestionIndex = -1;
            matches.forEach((article) => {
                const button = document.createElement("button");
                button.type = "button";
                button.className = "list-group-item list-group-item-action";
                button.textContent = `${article.label} (${Number(article.retail_price || 0).toFixed(2)})`;
                button.dataset.articleLabel = article.label || "";
                button.addEventListener("mousedown", (event) => {
                    event.preventDefault();
                });
                button.addEventListener("click", (event) => {
                    event.preventDefault();
                    addArticleToTable(article);
                    clearArticleSearch();
                    hideArticleSuggestions();
                    if (invoiceMessage) {
                        invoiceMessage.className = "alert d-none";
                        invoiceMessage.textContent = "";
                    }
                });
                articleSuggestions.appendChild(button);
            });

            articleSuggestions.classList.remove("d-none");
        };

        const updateArticleSuggestionHighlight = () => {
            if (!articleSuggestions) {
                return;
            }

            const suggestionButtons = Array.from(articleSuggestions.querySelectorAll(".list-group-item"));
            suggestionButtons.forEach((button, index) => {
                if (index === articleSuggestionIndex) {
                    button.classList.add("active");
                    button.scrollIntoView({ block: "nearest" });
                } else {
                    button.classList.remove("active");
                }
            });
        };

        const handleAddArticle = () => {
            const article = findArticle(articleSearch ? articleSearch.value : "");
            if (!article) {
                if (invoiceMessage) {
                    invoiceMessage.className = "alert alert-danger";
                    invoiceMessage.textContent = "Article not found in the price list.";
                }
                return;
            }

            addArticleToTable(article);
            clearArticleSearch();
            hideArticleSuggestions();
            if (invoiceMessage) {
                invoiceMessage.className = "alert d-none";
                invoiceMessage.textContent = "";
            }
        };

        addInvoiceArticleButton?.addEventListener("click", handleAddArticle);
        invoiceSecondaryAddArticleButton?.addEventListener("click", handleAddArticle);

        invoicePrimaryButton?.addEventListener("click", (event) => {
            if (currentInvoiceId) {
                event.preventDefault();
                resetInvoiceFormForNewInvoice();
            }
        });

        saveInvoiceChangesButton?.addEventListener("click", async () => {
            if (!currentInvoiceId || !invoiceUpdateEndpoint) {
                return;
            }

            if (invoiceMessage) {
                invoiceMessage.className = "alert d-none";
                invoiceMessage.textContent = "";
            }

            const payload = collectInvoicePayload();
            if (!validateInvoicePayload(payload)) {
                return;
            }

            payload.invoice_id = currentInvoiceId;
            await submitInvoiceRequest(invoiceUpdateEndpoint, payload, "update");
        });

        articleSearch?.addEventListener("input", () => {
            showArticleSuggestions(articleSearch.value);
        });

        articleSearch?.addEventListener("focus", () => {
            showArticleSuggestions(articleSearch.value);
        });

        articleSearch?.addEventListener("keydown", (event) => {
            if (!articleSuggestions || articleSuggestions.classList.contains("d-none")) {
                return;
            }

            const suggestionButtons = Array.from(articleSuggestions.querySelectorAll(".list-group-item"));
            if (!suggestionButtons.length) {
                return;
            }

            if (event.key === "ArrowDown") {
                event.preventDefault();
                articleSuggestionIndex = Math.min(articleSuggestionIndex + 1, suggestionButtons.length - 1);
                updateArticleSuggestionHighlight();
                return;
            }

            if (event.key === "ArrowUp") {
                event.preventDefault();
                articleSuggestionIndex = Math.max(articleSuggestionIndex - 1, 0);
                updateArticleSuggestionHighlight();
                return;
            }

            if (event.key === "Enter" && articleSuggestionIndex >= 0) {
                event.preventDefault();
                suggestionButtons[articleSuggestionIndex].click();
            }
        });

        document.addEventListener("click", (event) => {
            if (!articleSearch || !articleSuggestions) {
                return;
            }

            const clickedInsideSearch = articleSearch.contains(event.target);
            const clickedInsideSuggestions = articleSuggestions.contains(event.target);

            if (!clickedInsideSearch && !clickedInsideSuggestions) {
                hideArticleSuggestions();
            }
        });

        invoiceForm.addEventListener("submit", async (event) => {
            event.preventDefault();

            if (invoiceMessage) {
                invoiceMessage.className = "alert d-none";
                invoiceMessage.textContent = "";
            }

            if (currentInvoiceId) {
                return;
            }

            const payload = collectInvoicePayload();
            if (!validateInvoicePayload(payload)) {
                return;
            }
            await submitInvoiceRequest(invoiceForm.dataset.endpoint, payload, "create");
        });

        refreshInvoiceSummary();
        syncInvoiceDates();
        syncInvoiceCustomerTypeCode();
        if (invoicePrimaryButton) {
            invoicePrimaryButton.textContent = "Izdaj";
        }

        if (invoicePrefill && typeof invoicePrefill === "object") {
            if (invoicePrefill.customer) {
                customerFullName.value = invoicePrefill.customer.full_name || "";
                customerOib.value = invoicePrefill.customer.oib || "";
                if (customerLegal) {
                    customerLegal.checked = String(invoicePrefill.customer.legal || "0") === "1";
                }
                customerAddress.value = invoicePrefill.customer.address || "";
                customerCity.value = invoicePrefill.customer.city || "";
                customerCountry.value = invoicePrefill.customer.country || "";
                customerEmail.value = invoicePrefill.customer.email || "";
            }

            if (invoicePrefill.payment && invoicePayment) {
                const desiredPayment = invoicePrefill.payment === "cash" ? "cash_payment"
                    : invoicePrefill.payment === "card" ? "card_payment"
                    : invoicePrefill.payment === "transaction" ? "transactional_payment"
                    : invoicePrefill.payment;
                if (invoicePayment.querySelector(`option[value="${desiredPayment}"]`)) {
                    invoicePayment.value = desiredPayment;
                }
            }

            if (invoicePrefill.invoice_date && invoiceDate) {
                invoiceDate.value = invoicePrefill.invoice_date;
            }

            if (invoicePrefill.due_date && invoiceDueDate) {
                invoiceDueDate.value = invoicePrefill.due_date;
            }

            if (invoicePrefill.remark && invoiceRemark) {
                invoiceRemark.value = invoicePrefill.remark;
            }

            if (Array.isArray(invoicePrefill.articles)) {
                invoicePrefill.articles.forEach((prefillArticle) => {
                    const article = invoiceArticles.find((item) => Number(item.id || 0) === Number(prefillArticle.id || 0));
                    if (article) {
                        addArticleToTable(article, prefillArticle);
                    }
                });
            }

            syncInvoiceDates();
            syncInvoiceCustomerTypeCode();
            refreshInvoiceSummary();
        }
    }
});
