/* oxlint-disable react/only-export-components -- Provider and hook intentionally share this context. */
import { createContext, useContext, useState, useEffect } from "react";
import { api } from "../api";
const AuthContext = createContext(null);
export function AuthProvider({ children }) {
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");
    function clear() {
        localStorage.removeItem("token");
        localStorage.removeItem("user");
        setUser(null);
    }
    useEffect(() => {
        let active = true;
        window.addEventListener("auth-expired", clear);
        if (localStorage.getItem("token"))
            api("/user")
                .then((data) => {
                    if (active)
                        setUser({ ...data.user, token: localStorage.getItem("token") });
                })
                .catch((e) => {
                    if (active) setError(e.message);
                })
                .finally(() => {
                    if (active) setLoading(false);
                });
        else setLoading(false);
        return () => {
            active = false;
            window.removeEventListener("auth-expired", clear);
        };
    }, []);
    function login(data) {
        localStorage.setItem("token", data.token);
        setError("");
        setUser(data);
    }
    async function logout() {
        try {
            await api("/logout", { method: "POST" });
            clear();
        } catch (e) {
            setError(e.message);
        }
    }
    return (
        <AuthContext.Provider
            value={{
                user,
                loading,
                login,
                logout,
                updateUser: (data) => setUser((current) => ({ ...current, ...data })),
            }}
        >
            {error && (
                <div role="alert" className="alert alert-danger m-2">
                    {error} <button onClick={() => setError("")}>Dismiss</button>
                </div>
            )}
            {children}
        </AuthContext.Provider>
    );
}
export const useAuth = () => useContext(AuthContext);
