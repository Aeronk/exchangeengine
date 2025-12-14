import http from '../api/http'

export function useAuth() {
    const login = async (credentials) => {
        await http.get('/sanctum/csrf-cookie')
        return http.post('/login', credentials)
    }

    const logout = () => {
        return http.post('/logout')
    }

    const profile = () => {
        return http.get('/profile')
    }

    return { login, logout, profile }
}
